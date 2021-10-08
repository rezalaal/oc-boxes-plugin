<?php namespace OFFLINE\Boxes\Models;

use Cms\Classes\Controller;
use Model;
use October\Rain\Database\Models\DeferredBinding;
use OFFLINE\Boxes\Classes\YamlConfig;
use RainLab\Translate\Behaviors\TranslatableModel;
use RainLab\Translate\Classes\Translator;
use System\Models\File;

/**
 * @mixin \Eloquent
 */
class Instance extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];
    public $translatable = [];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'offline_boxes_instances';

    public $jsonable = ['data'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'is_enabled' => 'boolean',
        'partial' => 'string',
    ];

    public $attachOne = [
        'image' => File::class,
        'file' => File::class,
    ];

    public $attachMany = [
        'images' => File::class,
        'files' => File::class,
    ];

    /**
     * Cached partial YAML config.
     */
    private $partialConfig;

    /**
     * Render the Instance partial using the model data.
     */
    public function render(array $dataOverrides = []): string
    {
        $controller = Controller::getController() ?? new Controller();

        $clone = $this->buildClone($dataOverrides);

        return $controller->renderPartial($this->partial_htm, ['box' => $clone]);
    }

    public function getPartialNameAttribute()
    {
        $config = $this->getPartialConfig();

        return property_exists($config, 'name')
            ? $config->name
            : $this->partial;
    }

    /**
     * Build a clone of this model.
     *
     * This method builds a new Instance model, that contains the json decoded "data" property
     * as specific fields. This lets us use the model as if all values in the "data"
     * property were real fields.
     */
    public function buildClone(array $dataOverrides = [], $yaml = null): self
    {
        $yaml = $yaml ?? $this->getPartialConfig();
        $data = array_merge($this->data ?? [], $dataOverrides);

        if (property_exists($yaml, 'modelClass')) {
            $clone = new $yaml->modelClass;
        } else {
            $clone = new self;
        }

        $clone->id = $this->id;
        $clone->exists = $this->exists;

        $clone->setTranslatableFromConfig($yaml);
        $clone->forceFill($data);

        // Apply translated attributes.
        if ($clone->exists && $this->isClassExtendedWith(TranslatableModel::class)) {
            $obj = $clone->translations->first(function ($value, $key) {
                return $value->attributes['locale'] === Translator::instance()->getLocale();
            });
            $result = $obj ? json_decode($obj->attribute_data, true) : [];
            foreach ($result as $attribute => $value) {
                $clone->{$attribute} = $value;
            }
        }

        return $clone;
    }

    public function afterFetch()
    {
        if (!$this->partial) {
            return;
        }

        $this->setTranslatableFromConfig(
            $this->getPartialConfig(),
        );
    }

    /**
     * Get the cached partial config.
     */
    public function getPartialConfig()
    {
        if ($this->partialConfig) {
            return $this->partialConfig;
        }

        return $this->partialConfig = YamlConfig::instance()->configForPartial($this->partial);
    }

    /**
     * Returns all defined relation names of the model.
     */
    public function extractRelations()
    {
        return collect($this->getRelationDefinitions())->flatMap(function ($definition) {
            return array_keys($definition);
        })->toArray();
    }

    /**
     * Set the $translatable property if it is defined in the YAML config.
     */
    protected function setTranslatableFromConfig(object $config)
    {
        if (property_exists($config, 'translatable')) {
            $this->translatable = (array)$config->translatable;
        }
    }

    /**
     * The path to the yaml config is stored in the partial field.
     * This method returns the path the the neighboring htm file.
     * @return array|mixed|string|string[]
     */
    protected function getPartialHtmAttribute()
    {
        return str_replace(['.yml', '.yaml'], '', $this->partial);
    }


    /**
     * Override the original method so any custom model class is considered when
     * deferred bindings are in play.
     */
    protected function getDeferredBindingRecords($sessionKey)
    {
        $class = $this->getMorphClass();
        $model = new $class;

        if ($model !== get_class($this)) {
            $this->addRelationsFrom($model);
        }

        $binding = new DeferredBinding;

        $binding->setConnection($this->getConnectionName());

        return $binding
            ->where('master_type', $class)
            ->where('session_key', $sessionKey)
            ->get();
    }

    /**
     * Copy the relations from any model to $this.
     */
    protected function addRelationsFrom(Model $model)
    {
        collect($model->getRelationDefinitions())->each(function ($relations, $type) {
            collect($relations)->each(function ($relation, $name) use ($type) {
                $this->$type[$name] = $relation;
            });
        });
    }

    /**
     * Consider any custom model class when morphs are in play.
     */
    public function getMorphClass()
    {
        if (!$this->partial) {
            return get_class($this);
        }

        $config = $this->getPartialConfig();

        return property_exists($config, 'modelClass')
            ? $config->modelClass
            : get_class($this);
    }

    /**
     * List all partials form the current theme that has a YAML config.
     */
    public function getPartialOptions($_, $model)
    {
        $partials = YamlConfig::instance()->listPartials();

        if ($model->exists) {
            return $partials;
        }

        return [null => trans('offline.boxes::lang.please_select')] + $partials;
    }

}

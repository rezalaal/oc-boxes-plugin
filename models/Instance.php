<?php namespace OFFLINE\Boxes\Models;

use Cms\Classes\Controller;
use Model;
use OFFLINE\Boxes\Classes\YamlConfig;
use October\Rain\Database\Models\DeferredBinding;
use System\Models\File;

/**
 * @mixin \Eloquent
 */
class Instance extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

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

    public function getPartialOptions()
    {
        return YamlConfig::instance()->listPartials();
    }

    /**
     * Render the Instance partial using the model data.
     */
    public function render(array $dataOverrides = []): string
    {
        $controller = Controller::getController() ?? new Controller();

        $clone = $this->buildClone($dataOverrides);

        return $controller->renderPartial($this->partial_htm, ['box' => $clone]);
    }

    /**
     * Build a clone of this model.
     *
     * This method builds a new Instance model, that contains the json decoded "data" property
     * as specific fields. It also sets all available relations on the model instance.
     * This lets us use the model as if all values in the "data" property were real fields.
     * as
     */
    public function buildClone(array $dataOverrides = []): self
    {
        $yaml = YamlConfig::instance()->configForPartial($this->partial);
        $data = array_merge($this->data ?? [], $dataOverrides);

        if (property_exists($yaml, 'modelClass')) {
            $clone = new $yaml->modelClass;
        } else {
            $clone = new self;
        }

        $clone->id = $this->id;
        $clone->exists = true;
        $clone->forceFill($data);

        return $clone;
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
     * The path to the yaml config is stored in the partial field.
     * This method returns the path the the neighboring htm file.
     * @return array|mixed|string|string[]
     */
    protected function getPartialHtmAttribute()
    {
        return str_replace(['.yml', '.yaml'], '', $this->partial);
    }


    /**
     * getDeferredBindingRecords returns any outstanding binding records for this model
     * @return \October\Rain\Database\Collection
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

    protected function addRelationsFrom(Model $model)
    {
        collect($model->getRelationDefinitions())->each(function ($relations, $type) {
            collect($relations)->each(function ($relation, $name) use ($type) {
                $this->$type[$name] = $relation;
            });
        });
    }

    public function getMorphClass()
    {
        if (!$this->partial) {
            return get_class($this);
        }

        $yaml = YamlConfig::instance()->configForPartial($this->partial);

        return property_exists($yaml, 'modelClass') ? $yaml->modelClass : get_class($this);
    }

}

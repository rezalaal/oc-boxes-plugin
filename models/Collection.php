<?php namespace OFFLINE\Boxes\Models;

use Model;
use OFFLINE\Boxes\Classes\YamlConfig;

/**
 * @mixin \Eloquent
 */
class Collection extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sluggable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'offline_boxes_collections';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $slugs = ['slug' => 'name'];

    public $belongsTo = ['category' => Category::class];
    public $hasMany = ['instances' => Instance::class];

    public function morphInstances()
    {
        $yaml = YamlConfig::instance();

        $specialModelClasses = $this->instances->pluck('partial')->unique()->flatMap(function ($partial) use ($yaml) {

            $config = $yaml->configForPartial($partial);
            if (!property_exists($config, 'modelClass')) {
                return null;
            }

            $model = new $config->modelClass;
            $baseQuery = $model->newQuery()->where('partial', $partial);

            if (property_exists($config, 'eagerLoad')) {
                if ($config->eagerLoad === 'auto') {
                    $baseQuery->with($model->extractRelations());
                } elseif ($config->eagerLoad !== false) {
                    $baseQuery->with($config->eagerLoad);
                }
            }

            return $baseQuery->get();
        });

        $specialModelClassesLookup = $specialModelClasses->keyBy('id');

        $newInstances = $this->instances->map(function (Instance $instance) use ($specialModelClassesLookup) {
            return $specialModelClassesLookup->get($instance->id, $instance);
        });

        $this->setRelation('instances', $newInstances);

        return $newInstances;
    }
}

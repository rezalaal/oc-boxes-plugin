<?php namespace OFFLINE\Boxes\Components;

use Cms\Classes\ComponentBase;
use OFFLINE\Boxes\Models\Collection;
use OFFLINE\Boxes\Models\Instance;

class BoxList extends ComponentBase
{
    /**
     * @var \October\Rain\Database\Collection<Collection>
     */
    public $collection;
    /**
     * @var \October\Rain\Database\Collection<Instance>
     */
    public $boxes;

    public function componentDetails()
    {
        return [
            'name' => 'offline.boxes::lang.boxlist.name',
            'description' => 'offline.boxes::lang.boxlist.description'
        ];
    }

    public function defineProperties()
    {
        return [
            'id' => [
                'type' => 'dropdown',
                'name' => 'offline.boxes::lang.boxlist.id_param',
                'description' => 'offline.boxes::lang.boxlist.id_param_description',
            ]
        ];
    }

    public function onRun()
    {
        $this->collection = Collection
            ::with([
                'instances' => function ($q) {
                    $q->where('is_enabled', true);
                }
            ])
            ->find($this->property('id'));

        $this->boxes = $this->collection->morphInstances();
    }

    public function getIdOptions()
    {
        return Collection
            ::orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }
}

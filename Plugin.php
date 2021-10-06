<?php namespace OFFLINE\Boxes;

use Backend;
use October\Rain\Support\Facades\Event;
use OFFLINE\Boxes\Components\BoxList;
use OFFLINE\Boxes\FormWidgets\PartialFormFields;
use OFFLINE\Boxes\Models\Category;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            BoxList::class => 'boxList'
        ];
    }

    public function registerSettings()
    {
        return [
            'boxes_categories' => [
                'label' => 'offline.boxes::lang.categories',
                'description' => 'offline.boxes::lang.categories_description',
                'icon' => 'icon-list',
                'permissions' => ['offline.boxes.manage_categories'],
                'url' => Backend::url('offline/boxes/categories'),
                'category' => 'Boxes'
            ]
        ];
    }

    public function registerFormWidgets()
    {
        return [
            PartialFormFields::class => 'partialformfields',
        ];
    }

    public function boot()
    {
        Event::listen('backend.menu.extendItems', function (\Backend\Classes\NavigationManager $navigationManager) {
            Category::get()->each(function (Category $category) use ($navigationManager) {
                $navigationManager->addSideMenuItems('OFFLINE.Boxes', 'Boxes', [
                    $category->slug => [
                        'label' => $category->name,
                        'icon' => $category->icon,
                        'permissions' => ['offline.boxes.manage_collections'],
                        'url' => Backend::url('offline/boxes/collections/?category_id=' . $category->id),
                    ]
                ]);
            });
        });
    }
}

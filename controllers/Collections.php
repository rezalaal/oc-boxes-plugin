<?php namespace Offline\Boxes\Controllers;

use Backend\Behaviors\RelationController;
use Backend\Classes\Controller;
use BackendMenu;
use OFFLINE\Boxes\Models\Category;
use OFFLINE\Boxes\Models\Collection;
use OFFLINE\Boxes\Models\InstanceExample;

/**
 * Collections Backend Controller
 */
class Collections extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var string relationConfig file
     */
    public $relationConfig = 'config_relation.yaml';

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        if (!get('category_id')) {
            throw new \RuntimeException('[OFFLINE.Boxes] Missing ID parameter for collection');
        }

        $this->vars['category_id'] = get('category_id');

        $collection = Category::findOrFail($this->vars['category_id']);

        BackendMenu::setContext('OFFLINE.Boxes', 'boxes', $collection->slug);
    }

    public function listOverrideRecordUrl($record)
    {
        return 'offline/boxes/collections/update/' . $record->id . '?category_id=' . $this->vars['category_id'];
    }

    public function formGetRedirectUrl()
    {
        return 'offline/boxes/collections?category_id=' . $this->vars['category_id'];
    }

    public function formExtendModel(Collection $model)
    {
        $model->category_id = $this->vars['category_id'];
    }

    public function listExtendQuery($query)
    {
        $query->where('category_id', $this->vars['category_id']);
    }
}

<?php namespace OFFLINE\Boxes\Models;

use Model;

/**
 * @mixin \Eloquent
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'offline_boxes_categories';

    public $rules = [
        'name' => 'required',
    ];

    public $fillable = [
        'name',
    ];

    public $slugs = ['slug' => 'name'];

    public $hasMany = ['collections' => Collection::class];
}

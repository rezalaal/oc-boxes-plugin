<?php namespace OFFLINE\Boxes\Updates;

use OFFLINE\Boxes\Models\Category;
use Schema;
use October\Rain\Database\Updates\Migration;

class CreateOfflineBoxesCategories extends Migration
{
    public function up()
    {
        Schema::create('offline_boxes_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('icon')->default('icon-file');
            $table->string('slug')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Category::create(['name' => trans('offline.boxes::lang.pages')]);
    }

    public function down()
    {
        Schema::dropIfExists('offline_boxes_categories');
    }
}

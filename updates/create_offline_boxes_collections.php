<?php namespace OFFLINE\Boxes\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateOfflineBoxesCollections extends Migration
{
    public function up()
    {
        Schema::create('offline_boxes_collections', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('category_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offline_boxes_collections');
    }
}

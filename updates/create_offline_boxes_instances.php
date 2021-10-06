<?php namespace OFFLINE\Boxes\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateOfflineBoxesInstances extends Migration
{
    public function up()
    {
        Schema::create('offline_boxes_instances', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->boolean('is_enabled')->default(1);
            $table->string('partial');
            $table->text('data')->nullable();
            $table->integer('collection_id')->unsigned()->nullable();
            $table->integer('sort_order')->nullable()->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offline_boxes_instances');
    }
}

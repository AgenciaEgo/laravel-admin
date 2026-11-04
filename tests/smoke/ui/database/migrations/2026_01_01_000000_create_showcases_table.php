<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('showcases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('email')->nullable();
            $table->string('url')->nullable();
            $table->string('ip')->nullable();
            $table->string('mobile')->nullable();
            $table->string('color')->nullable();
            $table->decimal('currency', 10, 2)->nullable();
            $table->decimal('decimal', 10, 2)->nullable();
            $table->integer('number')->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->integer('slider')->nullable();
            $table->string('select')->nullable();
            $table->text('multiple_select')->nullable();
            $table->string('radio')->nullable();
            $table->string('radio_button')->nullable();
            $table->string('radio_card')->nullable();
            $table->text('checkbox')->nullable();
            $table->text('checkbox_button')->nullable();
            $table->text('checkbox_card')->nullable();
            $table->boolean('switch')->default(false);
            $table->date('date')->nullable();
            $table->dateTime('datetime')->nullable();
            $table->time('time')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->dateTime('datetime_start')->nullable();
            $table->dateTime('datetime_end')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('date_multiple')->nullable();
            $table->string('icon')->nullable();
            $table->text('tags')->nullable();
            $table->text('listbox')->nullable();
            $table->text('textarea')->nullable();
            $table->string('image')->nullable();
            $table->string('file')->nullable();
            $table->text('multiple_images')->nullable();
            $table->text('multiple_files')->nullable();
            $table->text('key_value')->nullable();
            $table->text('list')->nullable();
            $table->text('table')->nullable();
            $table->text('extra')->nullable();
            $table->string('timezone')->nullable();
            $table->integer('order')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();
        });

        Schema::create('showcase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showcase_id');
            $table->string('name');
            $table->integer('qty')->default(1);
            $table->string('kind')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('showcase_items');
        Schema::dropIfExists('showcases');
    }
};

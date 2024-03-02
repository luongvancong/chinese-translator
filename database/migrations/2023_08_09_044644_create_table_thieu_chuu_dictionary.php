<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thieu_chuu_dictionary', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('word')->unique()->nullable();
            $table->text('sino')->nullable();
            $table->text('meaning')->nullable();
            $table->text('meaning_html')->nullable();
            $table->dateTimeTz('created_at')->nullable();
            $table->dateTimeTz('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('thieu_chuu_dictionary');
    }
};

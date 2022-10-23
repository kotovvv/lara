<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQtytel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qtytel', function (Blueprint $table) {
          $table->id();
          $table->bigInteger('lid_id')->index('lid_id');
          $table->bigInteger('user_id')->index('user_id');
          $table->dateTime('date_time');
          $table->text('other');
        });
        Schema::table('lids', function (Blueprint $table) {
          $table->integer('qtytel');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qtytel', function (Blueprint $table) {
            //
        });
    }
}

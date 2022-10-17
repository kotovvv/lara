<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBtcListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('btc_list', function (Blueprint $table) {
          $table->id();
          $table->text('address');
          $table->boolean('used')->default(false)->index('used');
          $table->bigInteger('lid_id')->index('lid_id');
          $table->bigInteger('user_id')->index('user_id');
          $table->integer('summ');
          $table->integer('trx_count');
          $table->dateTime('date_time');
          $table->text('other');

        });
        Schema::table('lids', function (Blueprint $table) {
          $table->text('address');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('btc_list');
    }
}

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
        Schema::table('btc_list', function (Blueprint $table) {
          $table->id();
          $table->timestamps();
          $table->text('address');
          $table->boolean('used')->index('used');
          $table->bigInteger('lid_id');
          $table->bigInteger('user_id');
          $table->integer('summ');
          $table->integer('trx_count');
          $table->dateTime('date_time');
          $table->text('other');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('btc_list', function (Blueprint $table) {
            //
        });
    }
}

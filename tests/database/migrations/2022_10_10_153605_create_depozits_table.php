<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepozitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depozits', function (Blueprint $table) {
            $table->unsignedInteger('lid_id')->index('lid_id');
            $table->unsignedInteger('user_id')->index('user_id');
            $table->unsignedInteger('depozit');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depozits');
    }
}

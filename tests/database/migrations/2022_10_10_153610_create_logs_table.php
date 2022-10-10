<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->char('tel', 13)->index('logs_tel_index');
            $table->unsignedBigInteger('status_id')->nullable()->index('logs_status_id_foreign');
            $table->unsignedBigInteger('user_id')->index('logs_user_id_foreign');
            $table->string('text');
            $table->timestamps();
            $table->bigInteger('lid_id')->index('lid_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}

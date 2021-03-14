<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lids', function (Blueprint $table) {
            $table->id();
            $table->char('tel', 13)->unique();
            $table->string('name',60);
            $table->string('email',60);
            $table->unsignedBigInteger('provider_id');
            $table->foreign('provider_id')->references('id')->on('provider')->default(null);
            $table->unsignedBigInteger('status_id')->default(null);
            $table->foreign('status_id')->references('id')->on('status');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('user');
            $table->string('text')->default(null);
            $table->boolean('active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lids');
    }
}

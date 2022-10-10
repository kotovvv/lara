<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBalansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balans', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('balans');
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            
            $table->index(['user_id', 'date'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('balans');
    }
}

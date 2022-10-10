<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->rememberToken();
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->unsignedBigInteger('role_id')->index('users_role_id_foreign');
            $table->string('fio');
            $table->bigInteger('group_id')->nullable()->index('group_id');
            $table->integer('order')->default(99)->index('order');
            $table->char('pic', 128)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

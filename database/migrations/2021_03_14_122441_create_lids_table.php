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
            $table->foreignId('provider_id')->constrained('providers');
            $table->foreignId('status_id')->nullable()->constrained('statuses');
            $table->foreignId('user_id')->constrained('users');
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

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
            $table->char('tel', 16)->index('tel');
            $table->string('name', 60);
            $table->string('email', 60);
            $table->unsignedBigInteger('provider_id')->index();
            $table->unsignedBigInteger('status_id')->default(8)->index('lids_status_id_foreign');
            $table->unsignedBigInteger('user_id')->index('lids_user_id_foreign');
            $table->string('text')->default('''');
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->dateTime('ontime')->nullable()->index('ontime');
            $table->string('afilyator', 200);
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

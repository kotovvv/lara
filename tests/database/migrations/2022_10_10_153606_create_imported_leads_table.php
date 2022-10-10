<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportedLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->index('id');
            $table->unsignedBigInteger('lead_id')->index('lead_id');
            $table->unsignedBigInteger('api_key_id')->index('api_key_id');
            $table->dateTime('upload_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imported_leads');
    }
}

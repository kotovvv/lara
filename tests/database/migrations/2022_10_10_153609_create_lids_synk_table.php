<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLidsSynkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lids_synk', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique('id');
            $table->unsignedBigInteger('id_lead_vps')->index('id_lead_vps');
            $table->unsignedBigInteger('id_leads_loc')->index('id_leads_loc');
            $table->dateTime('datesynk')->index('datesynk');
            $table->text('text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lids_synk');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('guests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('booking_id');
            $table->foreignId('related_id')->nullable();
            $table->string('reference_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('age');
            $table->string('nationality');
            $table->enum('type', ['adult','kid','infant']);
            $table->enum('status', ['arriving','checked_in','no_show', 'booking_cancelled']);
            $table->foreignId('created_by')->nullable();

            $table->timestamps(0);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->dropIfExists('guests');
    }
}

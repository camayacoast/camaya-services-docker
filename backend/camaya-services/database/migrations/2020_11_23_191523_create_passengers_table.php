<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassengersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('passengers', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number');
            $table->string('booking_reference_number')->nullable();
            $table->string('guest_reference_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('age');
            $table->string('nationality')->nullable();
            $table->string('type');
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
        Schema::connection('camaya_booking_db')->dropIfExists('passengers');
    }
}

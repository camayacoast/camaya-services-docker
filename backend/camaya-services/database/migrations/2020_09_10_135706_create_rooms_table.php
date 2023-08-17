<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id');
            $table->foreignId('room_type_id');
            $table->string('number');
            $table->enum('room_status', ['clean', 'dirty', 'pickup', 'sanitized', 'inspected', 'out-of-service', 'out-of-order'])->nullable();
            $table->enum('fo_status', ['vacant', 'occupied'])->nullable();
            $table->enum('reservation_status', ['arrivals', 'arrived', 'stay-over', 'day-use', 'due-out', 'departed', 'not-reserved'])->nullable();
            $table->text('description')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('rooms');
    }
}

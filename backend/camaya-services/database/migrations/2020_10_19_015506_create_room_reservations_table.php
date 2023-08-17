<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('room_reservations', function (Blueprint $table) {
            $table->id();

            $table->string('room_id');
            $table->string('booking_reference_number')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('pending');
            $table->string('start_datetime');
            $table->string('end_datetime');
            $table->string('created_by')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('room_reservations');
    }
}

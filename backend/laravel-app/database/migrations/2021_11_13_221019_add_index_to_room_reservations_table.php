<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToRoomReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('room_reservations', function (Blueprint $table) {
            //
            $table->index(['start_datetime', 'end_datetime']);
            $table->index(['booking_reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('room_reservations', function (Blueprint $table) {
            //
            $table->dropIndex(['start_datetime', 'end_datetime']);
            $table->dropIndex(['booking_reference_number']);
        });
    }
}

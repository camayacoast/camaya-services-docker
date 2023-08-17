<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckinCheckoutColumnOnRoomReservationsTable extends Migration
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
            $table->dateTime('check_out_time')->nullable()->after('end_datetime');
            $table->dateTime('check_in_time')->nullable()->after('end_datetime');
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
            $table->dropColumn('check_out_time');
            $table->dropColumn('check_in_time');
        });
    }
}

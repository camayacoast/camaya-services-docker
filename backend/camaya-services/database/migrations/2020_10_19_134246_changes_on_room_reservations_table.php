<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesOnRoomReservationsTable extends Migration
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
            $table->foreignId('room_id')->nullable()->change();
            $table->foreignId('room_type_id')->after('room_id');
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
            $table->foreignId('room_id')->change();
            $table->dropColumn('room_type_id');
        });
    }
}

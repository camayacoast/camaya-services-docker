<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRoomStatusOnRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `rooms` MODIFY COLUMN `room_status` ENUM('clean', 'clean_inspected', 'dirty', 'dirty_inspected', 'pickup', 'sanitized', 'inspected', 'out-of-service', 'out-of-order')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `rooms` MODIFY COLUMN `room_status` ENUM('clean', 'dirty', 'pickup', 'sanitized', 'inspected', 'out-of-service', 'out-of-order')");
    }
}

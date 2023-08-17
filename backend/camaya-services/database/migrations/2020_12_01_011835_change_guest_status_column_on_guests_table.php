<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

class ChangeGuestStatusColumnOnGuestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `guests` CHANGE COLUMN `status` `status` ENUM('arriving', 'checked_in', 'no_show', 'booking_cancelled', 'room_checked_in', 'room_checked_out') NOT NULL DEFAULT 'arriving'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `guests` CHANGE COLUMN `status` `status` ENUM('arriving', 'checked_in', 'no_show', 'booking_cancelled') NOT NULL DEFAULT 'arriving'");
    }
}

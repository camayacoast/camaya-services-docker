<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

class AddVoidedStatusOnPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `payments` CHANGE COLUMN `status` `status` ENUM('pending', 'confirmed', 'cancelled', 'voided', 'refunded') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('camaya_booking_db')->statement("ALTER TABLE `payments` CHANGE COLUMN `status` `status` ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
}

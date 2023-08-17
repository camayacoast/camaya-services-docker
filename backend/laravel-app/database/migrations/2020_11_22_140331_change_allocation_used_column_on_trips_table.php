<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAllocationUsedColumnOnTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('trips', function (Blueprint $table) {
            //
            $table->renameColumn('allocation_used', 'seat_segment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('trips', function (Blueprint $table) {
            //
            $table->renameColumn('seat_segment_id', 'allocation_used');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToTripsTable extends Migration
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
            $table->index(['trip_number']);
            $table->index(['guest_reference_number']);
            $table->index(['booking_reference_number']);
            $table->index(['passenger_id']);
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
            $table->dropIndex(['trip_number']);
            $table->dropIndex(['guest_reference_number']);
            $table->dropIndex(['booking_reference_number']);
            $table->dropIndex(['passenger_id']);
        });
    }
}

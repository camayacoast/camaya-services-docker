<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LastPrintedAtNullableOnTripsTable extends Migration
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
            $table->dateTime('last_printed_at', 0)->nullable()->change();
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
            $table->dateTime('last_printed_at', 0)->change();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePaxColumnToUnsignedOnBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('bookings', function (Blueprint $table) {
            //
            $table->unsignedInteger('adult_pax')->default(1)->change();
            $table->unsignedInteger('kid_pax')->default(0)->change();
            $table->unsignedInteger('infant_pax')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('bookings', function (Blueprint $table) {
            //
            $table->integer('adult_pax')->default(1)->change();
            $table->integer('kid_pax')->default(0)->change();
            $table->integer('infant_pax')->default(0)->change();
        });
    }
}

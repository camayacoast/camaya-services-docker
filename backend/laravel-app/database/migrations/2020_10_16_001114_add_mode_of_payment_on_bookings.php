<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModeOfPaymentOnBookings extends Migration
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
            $table->string('mode_of_payment')->nullable()->after('eta');
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
            $table->dropColumn('mode_of_payment');
        });
    }
}

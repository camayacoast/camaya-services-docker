<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingReferenceNumberToGuestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('guests', function (Blueprint $table) {
            //
            $table->string('booking_reference_number', 20)->after('id');
            $table->dropColumn('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('guests', function (Blueprint $table) {
            //
            $table->dropColumn('booking_reference_number');
            $table->foreignId('booking_id')->after('id');
        });
    }
}

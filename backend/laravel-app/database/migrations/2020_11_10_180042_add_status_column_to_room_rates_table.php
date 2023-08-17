<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusColumnToRoomRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('room_rates', function (Blueprint $table) {
            //
            $table->enum('status', ['for_review', 'approved','denied'])->default('for_review')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('room_rates', function (Blueprint $table) {
            //
            $table->dropColumn('status');
        });
    }
}

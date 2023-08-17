<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('schedules', function (Blueprint $table) {
            //
            $table->index(['start_time', 'end_time']);
            $table->index(['trip_number']);
            $table->index(['trip_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('schedules', function (Blueprint $table) {
            //
            $table->dropIndex(['start_time', 'end_time']);
            $table->dropIndex(['trip_number']);
            $table->dropIndex(['trip_date']);
        });
    }
}

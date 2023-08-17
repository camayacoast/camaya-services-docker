<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToSeatSegmentAllowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('seat_segment_allows', function (Blueprint $table) {
            //
            $table->index(['seat_segment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('seat_segment_allows', function (Blueprint $table) {
            //
            $table->dropIndex(['seat_segment_id']);
        });
    }
}

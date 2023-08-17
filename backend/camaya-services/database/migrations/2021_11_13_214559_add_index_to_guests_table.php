<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToGuestsTable extends Migration
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
            $table->index(['booking_reference_number']);
            $table->index(['reference_number']);
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
            $table->dropIndex(['booking_reference_number']);
            $table->dropIndex(['reference_number']);
        });
    }
}

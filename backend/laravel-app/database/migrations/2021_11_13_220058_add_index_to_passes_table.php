<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('passes', function (Blueprint $table) {
            //
            $table->index(['pass_code']);
            $table->index(['booking_reference_number']);
            $table->index(['guest_reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('passes', function (Blueprint $table) {
            //
            $table->dropIndex(['pass_code']);
            $table->dropIndex(['booking_reference_number']);
            $table->dropIndex(['guest_reference_number']);
        });
    }
}

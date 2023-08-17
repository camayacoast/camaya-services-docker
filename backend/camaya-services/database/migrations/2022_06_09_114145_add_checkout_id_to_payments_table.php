<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckoutIdToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('bookings', function (Blueprint $table) {
            $table->dropColumn('checkout_id');
        });
        
        Schema::connection('camaya_booking_db')->table('payments', function (Blueprint $table) {
            $table->string('checkout_id')->nullable();
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
            $table->string('checkout_id')->nullable();
        });
        Schema::connection('camaya_booking_db')->table('payments', function (Blueprint $table) {
            $table->dropColumn('checkout_id');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromoRateOnPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('packages', function (Blueprint $table) {
            //
            $table->unsignedDecimal('promo_rate', 8, 2)->nullable()->after('weekend_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('packages', function (Blueprint $table) {
            //
            $table->dropColumn('promo_rate');
        });
    }
}

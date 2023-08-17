<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSellingPriceAndWalkinPriceOnInclusions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('inclusions', function (Blueprint $table) {
            //
            $table->unsignedDecimal('selling_price', 8, 2)->nullable()->after('price');
            $table->unsignedDecimal('walkin_price', 8, 2)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('inclusions', function (Blueprint $table) {
            //
            $table->dropColumn('selling_price');
            $table->dropColumn('walkin_price');
        });
    }
}

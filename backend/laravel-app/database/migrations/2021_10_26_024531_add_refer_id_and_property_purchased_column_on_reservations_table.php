<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferIdAndPropertyPurchasedColumnOnReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            //

            $table->string('referrer_property')->nullable()->after('sales_director_id');
            $table->foreignId('referrer_id')->nullable()->after('sales_director_id');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            //
            $table->dropColumn('referrer_property');
            $table->dropColumn('referrer_id');
        });
    }
}

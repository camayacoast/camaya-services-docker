<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowedDaysColumnOnProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('products', function (Blueprint $table) {
            //
            $table->json('allowed_days')->nullable()->after('serving_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('products', function (Blueprint $table) {
            //
            $table->dropColumn('allowed_days');
        });
    }
}

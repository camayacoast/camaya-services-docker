<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeModeOfTranspoColumnOnPackagesTable extends Migration
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
            $table->string('mode_of_transportation')->nullable()->change();
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
            $table->enum('mode_of_transportation', ['own_vehicle', 'via_ferry'])->change();
        });
    }
}

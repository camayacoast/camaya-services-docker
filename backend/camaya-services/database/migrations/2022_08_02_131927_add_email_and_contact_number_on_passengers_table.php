<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailAndContactNumberOnPassengersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::connection('camaya_booking_db')->table('tickets', function (Blueprint $table) {
            $table->string('email')->nullable()->after('status');
            $table->string('contact_number')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('tickets', function (Blueprint $table) {
            //
            $table->dropColumn('email');
            $table->dropColumn('contact_number');
        });
    }
}

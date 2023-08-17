<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentInternationalAddressOnClientInformationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_information', function (Blueprint $table) {
            //
            
            $table->boolean('current_home_address_international')->nullable()->default(0)->after('permanent_home_address_zip_code');
            $table->string('current_home_address_country')->nullable()->after('permanent_home_address_zip_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_information', function (Blueprint $table) {
            //
            $table->dropColumn('current_home_address_international');
            $table->dropColumn('current_home_address_country');
        });
    }
}

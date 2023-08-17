<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermanentCurrentHomeAddressOnClientInformationTable extends Migration
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
            $table->string('permanent_home_address_zip_code')->nullable()->after('permanent_home_address');
            $table->string('permanent_home_address_province')->nullable()->after('permanent_home_address');
            $table->string('permanent_home_address_city')->nullable()->after('permanent_home_address');
            $table->string('permanent_home_address_baranggay')->nullable()->after('permanent_home_address');
            $table->string('permanent_home_address_street')->nullable()->after('permanent_home_address');
            $table->string('permanent_home_address_house_number')->nullable()->after('permanent_home_address');

            $table->string('current_home_address_zip_code')->nullable()->after('current_home_address');
            $table->string('current_home_address_province')->nullable()->after('current_home_address');
            $table->string('current_home_address_city')->nullable()->after('current_home_address');
            $table->string('current_home_address_baranggay')->nullable()->after('current_home_address');
            $table->string('current_home_address_street')->nullable()->after('current_home_address');
            $table->string('current_home_address_house_number')->nullable()->after('current_home_address');
            
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
            $table->dropColumn('permanent_home_address_zip_code');
            $table->dropColumn('permanent_home_address_province');
            $table->dropColumn('permanent_home_address_city');
            $table->dropColumn('permanent_home_address_baranggay');
            $table->dropColumn('permanent_home_address_street');
            $table->dropColumn('permanent_home_address_house_number');

            $table->dropColumn('current_home_address_zip_code');
            $table->dropColumn('current_home_address_province');
            $table->dropColumn('current_home_address_city');
            $table->dropColumn('current_home_address_baranggay');
            $table->dropColumn('current_home_address_street');
            $table->dropColumn('current_home_address_house_number');
        });
    }
}

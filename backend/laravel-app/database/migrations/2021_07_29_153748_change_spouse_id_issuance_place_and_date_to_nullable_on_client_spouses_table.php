<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSpouseIdIssuancePlaceAndDateToNullableOnClientSpousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_spouses', function (Blueprint $table) {
            //
            $table->string('spouse_id_issuance_place')->nullable()->change();
            $table->date('spouse_id_issuance_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_spouses', function (Blueprint $table) {
            //
            $table->string('spouse_id_issuance_place')->change();
            $table->date('spouse_id_issuance_date')->change();
        });
    }
}

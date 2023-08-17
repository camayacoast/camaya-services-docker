<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMonthlyHouseholdIncomeOnClientInformationTable extends Migration
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
            $table->string('monthly_household_income')->change();
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
            $table->decimal('monthly_household_income', 8, 2)->change();
        });
    }
}

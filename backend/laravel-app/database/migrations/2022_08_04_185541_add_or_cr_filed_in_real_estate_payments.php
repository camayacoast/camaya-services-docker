<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrCrFiledInRealEstatePayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('real_estate_payments', function (Blueprint $table) {
            $table->string('cr_number')->nullable()->after('bank_account_number');
            $table->string('or_number')->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('real_estate_payments', function (Blueprint $table) {
            $table->dropColumn('cr_number');
            $table->dropColumn('or_number');
        });
    }
}

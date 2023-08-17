<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankDetailsOnRealEstatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('real_estate_payments', function (Blueprint $table) {
            //
            $table->string('bank_account_number')->nullable()->after('remarks');
            $table->string('check_number')->nullable()->after('remarks');
            $table->string('bank')->nullable()->after('remarks');
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
            //
            $table->dropColumn('bank');
            $table->dropColumn('bank_account_number');
            $table->dropColumn('check_number');
        });
    }
}

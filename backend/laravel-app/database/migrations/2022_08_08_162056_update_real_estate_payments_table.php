<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRealEstatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('real_estate_payments', function (Blueprint $table) {
            $table->bigInteger('cash_term_ledger_id')->nullable()->default(null);
            $table->float('discount', 8, 2)->nullable()->default(0);
            $table->integer('is_verified')->nullable()->default(0);
            $table->integer('verified_by')->nullable()->default(0);
            $table->integer('advance_payment')->nullable()->default(0);
            $table->dateTime('verified_date')->nullable();
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
            $table->dropColumn('cash_term_ledger_id');
            $table->dropColumn('discount');
            $table->dropColumn('is_verified');
            $table->dropColumn('verified_by');
            $table->dropColumn('advance_payment');
            $table->dropColumn('verified_date');
        });
    }
}

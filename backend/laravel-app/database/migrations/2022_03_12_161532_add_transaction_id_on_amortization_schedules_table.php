<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionIdOnAmortizationSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            //
            $table->string('transaction_id')->nullable()->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            //
            $table->dropColumn('transaction_id');
        });
    }
}

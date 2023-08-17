<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountColumnOnAmortizationFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_fees', function (Blueprint $table) {
            //
            $table->unsignedDecimal('amount', 8, 2)->after('type');
            $table->foreignId('created_by')->after('remarks');
            $table->foreignId('amortization_schedule_id')->nullable()->change();
            $table->string('payment_transaction_id')->nullable()->change();
            $table->dropColumn('penalty_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amortization_fees', function (Blueprint $table) {
            //
            $table->unsignedDecimal('penalty_amount', 8, 2)->after('type');
            $table->foreignId('amortization_schedule_id')->change();
            $table->string('payment_transaction_id')->change();
            $table->dropColumn('created_by');
            $table->dropColumn('amount');
        });
    }
}

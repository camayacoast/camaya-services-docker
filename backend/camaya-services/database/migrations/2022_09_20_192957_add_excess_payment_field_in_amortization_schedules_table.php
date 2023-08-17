<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExcessPaymentFieldInAmortizationSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->integer('excess_payment')->default(0);
            $table->string('type')->nullable();
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
            $table->dropColumn('excess_payment');
            $table->dropColumn('type');
        });
    }
}

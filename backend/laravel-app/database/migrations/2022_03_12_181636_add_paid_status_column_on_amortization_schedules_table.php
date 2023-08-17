<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidStatusColumnOnAmortizationSchedulesTable extends Migration
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
            $table->string('paid_status')->nullable()->after('date_paid');
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
            $table->dropColumn('paid_status');
        });
    }
}

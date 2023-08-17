<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRefundedOnPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('payments', function (Blueprint $table) {
            //
            $table->foreignId('refunded_by')->nullable()->after('voided_at');
            $table->dateTime('refunded_at', 0)->nullable()->after('refunded_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('payments', function (Blueprint $table) {
            //
            $table->dropColumn('refunded_by');
            $table->dropColumn('refunded_at');
        });
    }
}

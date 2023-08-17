<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatetypesOnPaymentsTable extends Migration
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
            $table->dropColumn('created_by');
            $table->dropColumn('voided_by');
            $table->dropColumn('paid_at');
            $table->dropColumn('amount');
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
            $table->string('created_by')->nullable();
            $table->string('voided_by')->nullable();
            $table->string('paid_at')->nullable();
            $table->string('amount')->nullable();
        });
    }
}

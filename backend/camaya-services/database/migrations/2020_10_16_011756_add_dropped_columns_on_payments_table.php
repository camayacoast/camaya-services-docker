<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDroppedColumnsOnPaymentsTable extends Migration
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
            $table->dateTime('paid_at',0)->nullable()->after('remarks');
            $table->foreignId('voided_by')->nullable()->after('paid_at');
            $table->foreignId('created_by')->nullable()->after('voided_at');
            $table->unsignedDecimal('amount',10, 2)->nullable()->before('remarks');
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
            $table->dropColumn('created_by');
            $table->dropColumn('voided_by');
            $table->dropColumn('paid_at');
            $table->dropColumn('amount');
        });
    }
}

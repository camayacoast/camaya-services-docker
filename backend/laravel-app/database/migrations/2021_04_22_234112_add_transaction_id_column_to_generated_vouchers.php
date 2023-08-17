<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionIdColumnToGeneratedVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('generated_vouchers', function (Blueprint $table) {
            //
            $table->string('transaction_reference_number')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('generated_vouchers', function (Blueprint $table) {
            //
            $table->dropColumn('transaction_reference_number');
        });
    }
}

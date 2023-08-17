<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionDetailsOnGeneratedVouchersTable extends Migration
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
            $table->string('mode_of_payment')->nullable()->after('paid_at');
            $table->string('payment_reference_number')->nullable()->after('paid_at');
            $table->string('provider')->nullable()->after('paid_at');
            $table->string('provider_reference_number')->nullable()->after('paid_at');

            $table->string('guest_reference_number')->nullable()->after('transaction_reference_number');
            $table->string('booking_reference_number')->nullable()->after('transaction_reference_number');
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
            $table->dropColumn('mode_of_payment');
            $table->dropColumn('payment_reference_number');
            $table->dropColumn('provider');
            $table->dropColumn('provider_reference_number');

            $table->dropColumn('guest_reference_number');
            $table->dropColumn('booking_reference_number');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentEncodeTypeOnRealEstatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('real_estate_payments', function (Blueprint $table) {
            //
            $table->string('payment_encode_type')->nullable()->after('payment_channel');
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
            //
            $table->dropColumn('payment_encode_type');
        });
    }
}

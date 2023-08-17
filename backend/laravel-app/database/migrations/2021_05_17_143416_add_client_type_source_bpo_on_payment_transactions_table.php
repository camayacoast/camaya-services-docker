<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientTypeSourceBpoOnPaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            //
            $table->string('client_type')->nullable()->after('item');
            $table->string('bpo')->nullable()->after('expires_at');
            $table->string('source')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            //
            $table->dropColumn('client_type');
            $table->dropColumn('source');
            $table->dropColumn('bpo');
        });
    }
}

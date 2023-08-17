<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NullablesOnPaymentsTable extends Migration
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

            $table->string('market_segmentation')->nullable()->change();
            // Please specify
            $table->string('remarks')->nullable()->change();
            $table->string('paid_at')->nullable()->change();
            $table->string('voided_by')->nullable()->change();
            $table->string('voided_at')->nullable()->change();
            $table->string('created_by')->nullable()->change();
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
            $table->string('market_segmentation')->change();
            $table->string('remarks')->change();
            $table->string('paid_at')->change();
            $table->string('voided_by')->change();
            $table->string('voided_at')->change();
            $table->string('created_by')->change();
        });
    }
}

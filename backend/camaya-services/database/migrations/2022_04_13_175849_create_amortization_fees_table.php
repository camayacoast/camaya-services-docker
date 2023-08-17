<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmortizationFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amortization_fees', function (Blueprint $table) {
            $table->id();

            $table->string('reservation_number');
            $table->foreignId('amortization_schedule_id');
            $table->unsignedDecimal('penalty_amount', 8, 2);
            $table->string('type');
            $table->string('payment_transaction_id');
            $table->string('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amortization_fees');
    }
}

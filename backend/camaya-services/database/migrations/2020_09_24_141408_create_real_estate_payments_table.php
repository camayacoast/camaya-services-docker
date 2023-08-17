<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealEstatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('real_estate_payments', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_id')->unique();
            $table->foreignId('client_id')->nullable();
            $table->string('client_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('contact_number');

            $table->string('sales_agent');
            $table->string('sales_manager');
            $table->string('currency');
            $table->unsignedDecimal('payment_amount', 10, 2);
            $table->dateTime('paid_at', 0)->nullable();
            $table->string('payment_type')->nullable();


            $table->string('payment_gateway')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_gateway_reference_number')->nullable();

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
        Schema::dropIfExists('real_estate_payments');
    }
}

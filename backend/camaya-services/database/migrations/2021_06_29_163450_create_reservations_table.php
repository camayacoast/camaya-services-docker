<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id'); 
            $table->string('status')->default('pending');
            $table->string('client_number')->nullable();
            $table->string('reservation_number');
            //

            $table->string('source')->nullable();

            // Property
            $table->string('property_type')->nullable();
            $table->string('subdivision');
            $table->string('block');
            $table->string('lot');
            $table->string('type')->nullable();
            $table->unsignedInteger('area')->nullable();
            $table->unsignedDecimal('price_per_sqm', 8, 2)->nullable();

            // Payments terms
            $table->dateTime('reservation_fee_date')->nullable();
            $table->unsignedDecimal('reservation_fee_amount', 12, 2);
            $table->string('payment_terms_type'); // cash / in_house_assisted_financing

            // common
            $table->unsignedDecimal('discount_amount', 12, 2);
            $table->boolean('with_twelve_percent_vat');

            // cash
            $table->boolean('with_five_percent_retention_fee')->nullable();
            $table->boolean('split_cash')->nullable();
            $table->unsignedInteger('number_of_cash_splits')->nullable();
            $table->dateTime('split_cash_start_date')->nullable();
            $table->dateTime('split_cash_end_date')->nullable();

            // in_house_assisted_financing
            $table->unsignedDecimal('downpayment_amount', 12, 2)->nullable();
            $table->dateTime('downpayment_due_date')->nullable();
            $table->unsignedInteger('number_of_years')->nullable();
            $table->unsignedDecimal('factor_rate', 10, 10)->nullable();
            $table->dateTime('monthly_amortization_due_date')->nullable();
            $table->boolean('split_downpayment')->nullable();
            $table->unsignedInteger('number_of_downpayment_splits')->nullable();
            $table->dateTime('split_downpayment_start_date')->nullable();
            $table->dateTime('split_downpayment_end_date')->nullable();

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
        Schema::dropIfExists('reservations');
    }
}

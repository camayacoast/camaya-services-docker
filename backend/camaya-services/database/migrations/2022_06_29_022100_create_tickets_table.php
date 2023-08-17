<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('reference_number')->unique();
            $table->string('group_reference_number');
            $table->string('trip_number');
            $table->foreignId('passenger_id');
            $table->string('ticket_type');
            $table->string('promo_type')->nullable();
    
            $table->unsignedDecimal('amount', 8, 2);
            $table->unsignedDecimal('discount', 8, 2)->nullable();
        
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_reference_number')->nullable();
            $table->string('mode_of_payment')->nullable(); // online, cash
            $table->string('payment_status')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_provider_reference_number')->nullable();
        
            $table->foreignId('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('refunded_by')->nullable();
            $table->dateTime('refunded_at')->nullable();
        
            $table->text('remarks')->nullable();
            $table->string('status');
            
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
        Schema::connection('camaya_booking_db')->dropIfExists('tickets');
    }
}

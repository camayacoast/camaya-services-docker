<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('folio_id')->nullable();
            $table->foreignId('inclusion_id')->nullable();
            $table->foreignId('voucher_id')->nullable();

            // billing_instruction_id
            $table->foreignId('billing_instruction_id')->nullable();
            // billing_instrunctions
            // $table->string('billing_instructions');
            // Send bill
            // First name, Last name, Address, Company

            // billing_intruction_id - 1
            // remarks / billing_instrunction
            // First name
            // Last name
            // Company
            // Address
            // Email

            $table->string('payment_reference_number');

            // $table->string('mode_of_payment');
            // Please specify
            // Online, Offline
            // Not yet sure if needed
            // Remove this?
            
            $table->string('mode_of_payment');
            // Cash, Online Payment, Bank Deposit, Bank Transfer, Voucher, Credit Card, Debit Card
            // Check payments
            // FOC - Cashier - ito ganito ...
            // City Ledger - will appear Charge to
            // City Ledger - ESLCC
            // City Ledger - ESTLC

            // Mode of payment: City Ledger - ESTLC, Amount: 5000
            
            // Type "Send Bill" pending status
            // 1000 confirmed status - Type "Send bill" - Type "Cash"
            // Send Bill payment instruction

            // $table->string('charge_to');
            // ESLCC, ESTLC, ESTVC, Magic Leaf, Contextus, Dev1, SLA, Tourism Integration, Others
            // Remove this field.

            $table->string('market_segmentation');
            // Please specify
            // 

            $table->enum('status',['pending', 'confirmed']); // Confirm

            $table->string('provider')->nullable();
            // Paypal, DragonPay, PesoPay, PayMaya

            $table->string('provider_reference_number')->nullable();
            $table->string('amount'); // THE AMOUNT
            $table->string('remarks');
            $table->string('paid_at');
            $table->string('voided_by');
            $table->string('voided_at');
            $table->string('created_by');
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
        Schema::connection('camaya_booking_db')->dropIfExists('payments');
    }
}

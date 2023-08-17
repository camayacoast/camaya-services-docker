<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('transaction_id');
            $table->string('item_transaction_id')->unique();
            $table->string('item');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->string('payment_type')->nullable();
            $table->enum('status', ['created', 'paid', 'refunded', 'cancelled']);
            $table->string('payment_channel');
            $table->string('payment_code')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('amount');

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
        Schema::dropIfExists('payment_transactions');
    }
}

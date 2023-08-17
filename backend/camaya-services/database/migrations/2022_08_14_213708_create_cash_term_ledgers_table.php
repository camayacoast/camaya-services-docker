<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTermLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_term_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number');
            $table->string('transaction_id')->nullable();
            $table->unsignedInteger('number');
            $table->dateTime('due_date');
            $table->unsignedDecimal('amount', 10, 2);
            $table->dateTime('date_paid')->nullable();
            $table->string('paid_status')->nullable();
            $table->unsignedDecimal('amount_paid', 10, 2)->nullable();
            $table->string('pr_number')->nullable();
            $table->string('or_number')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('payment_gateway_reference_number')->nullable();
            $table->string('bank')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('check_number')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('datetime')->nullable();
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
        Schema::dropIfExists('cash_term_ledgers');
    }
}

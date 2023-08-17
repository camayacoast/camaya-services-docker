<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTermPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_term_penalties', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number');
            $table->foreignId('cash_term_ledger_id');
            $table->unsignedInteger('number');
            $table->unsignedDecimal('penalty_amount', 8, 2);
            $table->string('type');
            $table->dateTime('paid_at')->nullable();
            $table->unsignedDecimal('amount_paid', 8, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->integer('system_generated')->default(0);
            $table->float('discount', 8, 2)->nullable()->default(0);
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
        Schema::dropIfExists('cash_term_penalties');
    }
}

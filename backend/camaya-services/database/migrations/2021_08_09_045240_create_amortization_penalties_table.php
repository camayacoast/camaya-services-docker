<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmortizationPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amortization_penalties', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number');
            $table->foreignId('amortization_schedule_id');
            $table->unsignedDecimal('penalty_amount', 8, 2);
            $table->string('type');
            $table->dateTime('paid_at')->nullable();
            $table->unsignedDecimal('amount_paid', 8, 2)->nullable();
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
        Schema::dropIfExists('amortization_penalties');
    }
}

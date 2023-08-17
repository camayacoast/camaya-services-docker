<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmortizationSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amortization_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number');
            $table->unsignedInteger('number');
            $table->dateTime('due_date');
            $table->unsignedDecimal('amount', 8, 2);
            $table->dateTime('date_paid')->nullable();
            $table->unsignedDecimal('amount_paid', 8, 2)->nullable();
            $table->string('pr_number')->nullable();
            $table->string('or_number')->nullable();
            $table->string('account_number')->nullable();
            $table->unsignedDecimal('principal', 8, 2);
            $table->unsignedDecimal('interest', 8, 2);
            $table->unsignedDecimal('balance', 10, 2);
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
        Schema::dropIfExists('amortization_schedules');
    }
}

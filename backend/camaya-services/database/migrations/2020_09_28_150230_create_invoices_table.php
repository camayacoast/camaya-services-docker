<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference_number');
            $table->string('reference_number');
            $table->unsignedInteger('batch_number');

            $table->enum('status', ['sent', 'paid', 'overdue', 'void', 'partial', 'draft', 'disputed']);

            $table->dateTime('due_datetime', 0)->nullable();
            $table->dateTime('paid_at', 0)->nullable();
            $table->unsignedDecimal('total_cost', 10, 2);
            $table->unsignedDecimal('discount', 2, 2)->nullable();
            $table->unsignedDecimal('sales_tax', 8, 2)->nullable();
            $table->unsignedDecimal('grand_total', 10, 2);
            $table->unsignedDecimal('total_payment', 10, 2);
            $table->unsignedDecimal('balance', 10, 2)->nullable();
            $table->unsignedDecimal('change', 10, 2)->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by');
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();

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
        Schema::connection('camaya_booking_db')->dropIfExists('invoices');
    }
}

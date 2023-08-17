<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInclusionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('inclusions', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference_number');
            $table->foreignId('invoice_id');
            $table->foreignId('guest_id')->nullable();
            $table->string('code');
            $table->string('item');
            $table->string('description')->nullable();
            $table->dateTime('serving_time', 0)->nullable();
            $table->dateTime('used_at', 0)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedDecimal('original_price', 8, 2);
            $table->unsignedDecimal('price', 8, 2);
            $table->unsignedDecimal('discount', 2, 2)->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('inclusions');
    }
}

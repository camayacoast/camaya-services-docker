<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneratedVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('generated_vouchers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voucher_id');
            $table->string('voucher_code');
            $table->string('type');
            $table->string('description')->nullable();
            $table->string('availability');
            $table->string('category')->nullable();
            $table->string('mode_of_transportation')->nullable();
            $table->string('allowed_days');
            $table->string('exclude_days')->nullable();
            $table->string('price');
            $table->string('validity_start_date')->nullable();
            $table->string('validity_end_date')->nullable();
            $table->string('voucher_status');
            $table->dateTime('used_at')->nullable();
            $table->string('payment_status');
            $table->dateTime('paid_at')->nullable();

            $table->foreignId('created_by')->nullable();
            
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
        Schema::connection('camaya_booking_db')->dropIfExists('generated_vouchers');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('description')->nullable();
            $table->string('availability');
            $table->string('category')->nullable();
            $table->string('mode_of_transportation');
            $table->json('allowed_days');
            $table->json('exclude_days')->nullable();
            $table->dateTime('selling_start_date')->nullable();
            $table->dateTime('selling_end_date')->nullable();
            $table->dateTime('booking_start_date')->nullable();
            $table->dateTime('booking_end_date')->nullable();
            $table->string('status');
            $table->unsignedDecimal('price',8,2);
            $table->string('quantity_per_day')->nullable();
            $table->string('stocks')->nullable();
            
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
        Schema::connection('camaya_booking_db')->dropIfExists('vouchers');
    }
}

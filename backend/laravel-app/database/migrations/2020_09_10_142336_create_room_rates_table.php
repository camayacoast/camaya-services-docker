<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('room_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->unsignedDecimal('room_rate', 8, 2);
            $table->json('days_interval')->nullable();
            $table->json('exclude_days')->nullable();
            $table->text('description')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('room_rates');
    }
}

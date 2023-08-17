<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number');
            $table->string('ticket_reference_number');
            $table->string('guest_reference_number')->nullable();
            $table->string('booking_reference_number')->nullable();
            $table->foreignId('passenger_id')->nullable();
            $table->string('seat_number', 10)->nullable();
            $table->string('status'); // 'pending','confirmed','checked_in','no_show','boarded','arrived','cancelled'
            $table->unsignedInteger('allocation_used');
            $table->unsignedInteger('printed')->default(0);
            $table->dateTime('last_printed_at',0);
            $table->dateTime('checked_in_at',0)->nullable();
            $table->dateTime('boarded_at',0)->nullable();
            $table->dateTime('cancelled_at',0)->nullable();
            $table->dateTime('no_show_at',0)->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('trips');
    }
}

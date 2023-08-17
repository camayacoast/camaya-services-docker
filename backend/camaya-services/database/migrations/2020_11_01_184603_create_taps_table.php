<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('taps', function (Blueprint $table) {
            $table->id();
            // time status and message
            $table->string('code');
            $table->dateTime('tap_datetime', 0);
            $table->string('status')->nullable(); // ['valid', 'valid_not_allowed', 'valid_not_yet_started', 'valid_expired', 'valid_consumed', 'invalid'])->nullable();
            $table->string('message')->nullable();

            // location, kiosk used and type
            $table->string('location')->nullable();
            $table->foreignId('kiosk_id');
            $table->string('type')->nullable(); // entry, exit, consume
            $table->string('pass_code')->nullable();
            
            // Secondary
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('taps');
    }
}

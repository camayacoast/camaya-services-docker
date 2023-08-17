<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyGuestLimitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('daily_guest_limits', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('limit');
            $table->string('category');
            $table->string('status');
            $table->foreignId('created_by');
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('daily_guest_limits');
    }
}

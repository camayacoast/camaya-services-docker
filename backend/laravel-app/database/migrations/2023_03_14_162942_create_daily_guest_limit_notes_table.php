<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyGuestLimitNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('daily_guest_limit_notes', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date');
            $table->text('note')->nullable();
            $table->foreignId('updated_by');
            $table->index(['date']);

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
        Schema::connection('camaya_booking_db')->dropIfExists('daily_guest_limit_notes');
    }
}

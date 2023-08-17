<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeeTimeGuestSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('tee_time_guest_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference_number');
            $table->string('guest_reference_number');
            $table->string('tee_time_schedule_id');
            $table->string('status')->nullable();
            $table->string('deleted_by')->nullable();
            $table->string('deleted_at')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('tee_time_guest_schedules');
    }
}

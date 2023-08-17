<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id');
            $table->foreignId('transportation_id')->nullable();
            $table->string('trip_number');
            $table->string('status'); // active, delayed, cancelled
            $table->date('trip_date');
            $table->time('start_time', 0);
            $table->time('end_time', 0);
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('deleted_by')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('schedules');
    }
}

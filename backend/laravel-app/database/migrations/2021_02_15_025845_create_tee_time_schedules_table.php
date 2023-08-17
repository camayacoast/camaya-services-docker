<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeeTimeSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('tee_time_schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTimeTz('date');
            $table->time('time', $precision = 0);
            $table->string('entity');
            $table->integer('allocation')->default(0);
            $table->string('mode_of_transportation');
            $table->string('status');
            $table->foreignId('created_by');
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
        Schema::connection('camaya_booking_db')->dropIfExists('tee_time_schedules');
    }
}

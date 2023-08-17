<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeatSegmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('seat_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seat_allocation_id');
            $table->string('name');
            $table->unsignedInteger('allocated');
            $table->unsignedInteger('active'); // on-going
            $table->string('booking_type'); // all, DT, ON
            $table->string('status'); // published, unpublished
            $table->string('trip_link');
            $table->foreignId('updated_by');
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
        Schema::connection('camaya_booking_db')->dropIfExists('seat_segments');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference_number')->nullable();
        
            $table->string('action');
            $table->text('description');

            $table->string('model');
            $table->foreignId('model_id');

            $table->json('properties')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('activity_logs');
    }
}

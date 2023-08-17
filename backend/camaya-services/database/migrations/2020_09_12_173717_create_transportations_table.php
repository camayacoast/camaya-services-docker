<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('transportations', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->string('mode');
            $table->string('capacity');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_infant')->nullable();
            $table->enum('status', ['unavailable', 'available', 'maintenance'])->nullable();
            $table->json('current_location')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('transportations');
    }
}

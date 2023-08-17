<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeatAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('seat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id');
            $table->string('name'); //
            $table->string('category')->nullable(); // 
            $table->unsignedInteger('quantity');
            $table->json('allowed_roles')->nullable(); // 
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
        Schema::connection('camaya_booking_db')->dropIfExists('seat_allocations');
    }
}

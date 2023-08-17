<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('land_allocations', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->unsignedInteger('allocation')->default(0);
            $table->unsignedInteger('used')->default(0);
            $table->string('entity');
            $table->foreignId('owner_id')->nullable();
            $table->json('allowed_roles');
            $table->string('status');
            $table->foreignId('created_by');
            $table->foreignId('updated_by')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('land_allocations');
    }
}

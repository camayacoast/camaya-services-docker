<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('room_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('max_capacity')->nullable();
            $table->unsignedDecimal('rack_rate', 8, 2);
            $table->string('cover_image_path')->nullable();
            $table->enum('status', ['enabled', 'disabled'])->default('disabled');

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
        Schema::connection('camaya_booking_db')->dropIfExists('room_types');
    }
}

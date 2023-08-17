<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('properties', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->unsignedInteger('floors')->default(1)->nullable();
            $table->string('cover_image_path')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed', 'under-construction', 'under-renovation']);

            $table->timestamps(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->dropIfExists('properties');
    }
}

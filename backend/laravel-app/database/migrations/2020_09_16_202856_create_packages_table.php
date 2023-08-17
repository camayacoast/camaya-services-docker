<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('packages', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('description')->nullable();
            $table->string('availability');
            $table->enum('mode_of_transportation', ['own_vehicle', 'via_ferry']);
            $table->json('allowed_days');
            $table->json('exclude_days')->nullable();
            $table->dateTime('selling_start_date')->nullable();
            $table->dateTime('selling_end_date')->nullable();
            $table->dateTime('booking_start_date')->nullable();
            $table->dateTime('booking_end_date')->nullable();
            // $table->json('room_type_inclusions');
            // $table->json('product_inclusions');
            $table->enum('status', ['unpublished', 'published', 'expired', 'ended']);
            $table->unsignedDecimal('regular_price', 8, 2);
            $table->unsignedDecimal('selling_price', 8, 2);
            $table->unsignedDecimal('walkin_price', 8, 2)->nullable();
            $table->unsignedInteger('min_adult')->nullable();
            $table->unsignedInteger('max_adult')->nullable();
            $table->unsignedInteger('min_kid')->nullable();
            $table->unsignedInteger('max_kid')->nullable();
            $table->unsignedInteger('min_infant')->nullable();
            $table->unsignedInteger('max_infant')->nullable();
            $table->unsignedInteger('quantity_per_day')->nullable();
            $table->unsignedInteger('stocks')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('packages');
    }
}

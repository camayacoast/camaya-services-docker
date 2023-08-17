<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::connection('camaya_booking_db')->create('addons', function (Blueprint $table) {
            $table->id();

            $table->string('booking_reference_number');
            $table->string('guest_reference_number')->nullable();

            $table->string('code');
            $table->string('type')->nullable();
            $table->dateTime('date')->nullable();

            $table->string('status')->nullable();

            $table->foreignId('created_by')->nullable();

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
        //
        Schema::connection('camaya_booking_db')->dropIfExists('addons');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transportation_id');
            $table->string('number');
            $table->enum('type', ['economy', 'business']);
            $table->enum('status', ['active', 'out-of-order']);
            $table->enum('auto_check_in_status', ['public', 'restricted', 'vip'])->nullable();
            $table->unsignedInteger('order')->nullable();
            
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
        Schema::connection('camaya_booking_db')->dropIfExists('seats');
    }
}

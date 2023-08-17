<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('passes', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference_number')->nullable();
            $table->string('guest_reference_number')->nullable();
            $table->string('card_number')->nullable();
            $table->foreignId('inclusion_id')->nullable();
            $table->string('pass_code');

            $table->string('description')->nullable();
            $table->string('category'); // (consumable, reusable)
            $table->unsignedInteger('count')->nullable(); // (consumable, reusable)
            $table->json('interfaces')->nullable(); // 
            $table->string('mode')->nullable(); // entry, exit, redeem
            $table->string('type'); // (guest_pass, parking_gate_pass, boarding_pass)
            $table->enum('status', ['created', 'consumed', 'used', 'voided'])->nullable(); // (created, consumed, voided)
            $table->dateTime('usable_at', 0)->nullable();
            $table->dateTime('expires_at', 0)->nullable();

            $table->string('created_by')->nullable();
            $table->string('deleted_by')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('passes');
    }
}

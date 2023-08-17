<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStubsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('stubs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // commercial_gate_entry_pass, commercial_gate_exit_pass
            $table->json('interfaces')->nullable(); // commercial_gate, main_gate, parking_gate
            $table->string('mode'); // entry, exit, redeem
            $table->unsignedInteger('count');
            $table->string('category'); // consumable, reusable
            $table->string('starttime')->nullable();
            $table->string('endtime')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('stubs');
    }
}

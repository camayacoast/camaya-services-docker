<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViberSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('viber_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('viber_token');
            $table->string('viber_id')->unique();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('country')->nullable();
            $table->enum('status', ['unverified', 'subscribed', 'unsubscribed', 'kicked', 'banned']);
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
        Schema::dropIfExists('viber_subscribers');
    }
}

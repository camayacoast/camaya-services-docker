<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('client_number');
            $table->integer('area');
            $table->string('lot_number');
            $table->string('block_number');
            $table->string('subdivision');
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
        Schema::dropIfExists('client_properties');
    }
}

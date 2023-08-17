<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLotInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lot_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('subdivision');
            $table->string('block');
            $table->string('lot');
            $table->unsignedInteger('area')->nullable();
            $table->string('type')->nullable();
            $table->unsignedDecimal('price_per_sqm', 10, 2)->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('lot_inventories');
    }
}

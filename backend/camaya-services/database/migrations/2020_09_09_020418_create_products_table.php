<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('availability');
            $table->json('serving_time')->nullable();
            $table->integer('quantity_per_day')->nullable();
            $table->unsignedDecimal('price', 8,2);
            $table->unsignedDecimal('walkin_price', 8,2)->nullable();
            $table->unsignedDecimal('kid_price', 8,2)->nullable();
            $table->unsignedDecimal('infant_price', 8,2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('auto_include')->default(0);
            $table->string('addon_of')->nullable();

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
        Schema::connection('camaya_booking_db')->dropIfExists('products');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->foreignId('user_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('reference_number')->unique();
            $table->dateTime('start_datetime', 0);
            $table->dateTime('end_datetime', 0);
            $table->integer('adult_pax')->default(1);
            $table->integer('kid_pax')->default(0);
            $table->integer('infant_pax')->default(0);
            $table->enum('status', ['draft', 'pending', 'confirmed', 'cancelled', 'void']);
            $table->integer('rating')->default(0);
            $table->string('label')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('type', ['DT', 'ON', 'OTHER']);
            $table->string('source')->nullable();
            $table->enum('mode_of_transportation', ['undecided', 'own_vehicle', 'camaya_transportation', 'camaya_vehicle', 'van_rental', 'company_vehicle']);
            $table->string('eta')->nullable();
            $table->dateTime('approved_at', 0)->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('auto_cancel_at', 0)->nullable();
            $table->foreignId('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable();
            $table->string('reason_for_cancellation')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('bookings');
    }
}

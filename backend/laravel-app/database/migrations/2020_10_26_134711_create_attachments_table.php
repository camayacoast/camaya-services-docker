<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference_number')->nullable();
            $table->foreignId('related_id')->nullable();
            $table->string('type')->nullable();
            $table->string('file_name');
            $table->string('content_type');
            $table->integer('file_size');
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('deleted_by')->nullable();
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
        Schema::connection('camaya_booking_db')->dropIfExists('attachments');
    }
}

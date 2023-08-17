<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientInformationAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_information_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('client_information_id');
            $table->string('status')->default('for_review');
            $table->string('type')->nullable();
            $table->string('file_name');
            $table->string('content_type');
            $table->unsignedInteger('file_size');
            $table->string('file_path');
            $table->string('description')->nullable();
            $table->foreignId('created_by');
            $table->string('deleted_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
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
        Schema::dropIfExists('client_information_attachments');
    }
}

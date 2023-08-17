<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentDetailAttachments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_detail_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->string('type')->nullable();
            $table->string('file_name');
            $table->string('content_type');
            $table->unsignedInteger('file_size');
            $table->string('file_path');
            $table->string('status')->default('active');
            $table->string('created_by')->nullable();
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
        Schema::dropIfExists('payment_detail_attachments');
    }
}

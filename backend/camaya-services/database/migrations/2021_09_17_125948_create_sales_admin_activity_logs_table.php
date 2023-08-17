<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesAdminActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_admin_activity_logs', function (Blueprint $table) {
            $table->id();
            
            // action
            $table->string('action');
            // description
            $table->text('description');

            // type
            $table->string('type');
            // reference_id
            $table->foreignId('reference_id');

            // data
            $table->json('data')->nullable();

            // created_by

            $table->foreignId('created_by');
            
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
        Schema::dropIfExists('sales_admin_activity_logs');
    }
}

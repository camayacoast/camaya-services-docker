<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCreatedByToNullableOnAdditionalEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('additional_emails', function (Blueprint $table) {
            //
            $table->foreignId('created_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('additional_emails', function (Blueprint $table) {
            //
            $table->foreignId('created_by')->change();
        });
    }
}

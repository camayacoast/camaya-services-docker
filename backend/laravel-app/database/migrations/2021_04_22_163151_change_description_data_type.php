<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDescriptionDataType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('vouchers', function (Blueprint $table) {
            //
            $table->text('description')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('vouchers', function (Blueprint $table) {
            //
            $table->string('description')->change();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestReferenceNumberToInclusionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('inclusions', function (Blueprint $table) {
            //
            $table->string('guest_reference_number')->nullable()->after('guest_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('inclusions', function (Blueprint $table) {
            //
            $table->dropColumn('guest_reference_number');
        });
    }
}

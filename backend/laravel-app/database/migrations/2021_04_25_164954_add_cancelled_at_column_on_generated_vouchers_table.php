<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelledAtColumnOnGeneratedVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('generated_vouchers', function (Blueprint $table) {
            //
            $table->dateTime('cancelled_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('generated_vouchers', function (Blueprint $table) {
            //
            $table->dropColumn('cancelled_at');
        });
    }
}

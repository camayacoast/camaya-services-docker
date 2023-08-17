<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalesDirectorIdAgentIdColumnOnBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('bookings', function (Blueprint $table) {
            //
            $table->foreignId('sales_director_id')->nullable()->after('customer_id');
            $table->foreignId('agent_id')->nullable()->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('bookings', function (Blueprint $table) {
            //
            $table->dropColumn('sales_director_id');
            $table->dropColumn('agent_id');
        });
    }
}

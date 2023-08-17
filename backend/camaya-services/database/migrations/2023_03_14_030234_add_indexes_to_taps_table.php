<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('taps', function (Blueprint $table) {
            //
            $table->index(['code']);
            $table->index(['location']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['tap_datetime'], 'tap_date_indexes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('taps', function (Blueprint $table) {
            //
            $table->dropIndex(['code']);
            $table->dropIndex(['location']);
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex('tap_date_indexes');
        });
    }
}

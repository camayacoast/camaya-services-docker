<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityColumnPackageInclusionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('package_inclusions', function (Blueprint $table) {
            //
            $table->unsignedInteger('quantity')->nullable()->after('related_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('package_inclusions', function (Blueprint $table) {
            //
            $table->dropColumn('quantity');
        });
    }
}

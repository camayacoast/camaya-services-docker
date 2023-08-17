<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLotInventoriesPropertyTypeSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('lot_inventories', function (Blueprint $table) {
        //     $table->dropColumn('property_type');
        // });
        Schema::table('lot_inventories', function (Blueprint $table) {
            $table->string('property_type')->nullable()->default('lot')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lot_inventories', function (Blueprint $table) {
            $table->string('property_type')->nullable()->change();
        });
    }
}

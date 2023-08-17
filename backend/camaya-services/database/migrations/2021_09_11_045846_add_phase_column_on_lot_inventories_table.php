<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhaseColumnOnLotInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lot_inventories', function (Blueprint $table) {
            //
            $table->string('phase')->nullable()->after('subdivision');
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
            //
            $table->dropColumn('phase');
        });
    }
}

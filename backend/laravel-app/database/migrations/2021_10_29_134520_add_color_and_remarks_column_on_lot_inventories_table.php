<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorAndRemarksColumnOnLotInventoriesTable extends Migration
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
            $table->string('subdivision_name')->nullable()->after('subdivision');
            $table->string('client_number')->nullable()->after('status');
            $table->text('remarks')->nullable()->after('status');
            $table->string('color')->nullable()->after('status');
            $table->string('status2')->nullable()->after('status');
            
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
            $table->dropColumn('subdivision_name');
            $table->dropColumn('remarks');
            $table->dropColumn('color');
            $table->dropColumn('client_number');
            $table->dropColumn('status2');
        });
    }
}

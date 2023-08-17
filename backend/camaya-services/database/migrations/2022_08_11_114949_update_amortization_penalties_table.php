<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAmortizationPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_penalties', function (Blueprint $table) {
            $table->integer('system_generated')->default(0);
            $table->float('discount', 8, 2)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amortization_penalties', function (Blueprint $table) {
            $table->dropColumn('system_generated');
            $table->dropColumn('discount');
        });
    }
}

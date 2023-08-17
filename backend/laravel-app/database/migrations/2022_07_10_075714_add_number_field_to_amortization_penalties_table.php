<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumberFieldToAmortizationPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_penalties', function (Blueprint $table) {
            $table->bigInteger('number')->nullable()->after('amortization_schedule_id');
            $table->integer('is_old')->default(1);
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
            $table->dropColumn('number');
            $table->dropColumn('is_old');
        });
    }
}

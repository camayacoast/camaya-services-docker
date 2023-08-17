<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssistedByToClientProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            //
            $table->string('assisted_by')->before('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            //
            $table->dropColumn('assisted_by');
        });
    }
}

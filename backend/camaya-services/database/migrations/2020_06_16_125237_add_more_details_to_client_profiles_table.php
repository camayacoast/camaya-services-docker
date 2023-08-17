<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreDetailsToClientProfilesTable extends Migration
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
            $table->date('birth_date')->after('golf_membership')->nullable();
            $table->string('birth_place')->after('birth_date')->nullable();
            $table->string('nationality')->after('birth_place')->nullable();
            $table->string('residence_address')->after('nationality')->nullable();
            $table->string('telephone_number')->after('residence_address')->nullable();

            $table->string('photo')->before('valid_id')->nullable();
            $table->string('valid_id')->before('created_at')->nullable();
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
            $table->dropColumn('birth_date');
            $table->dropColumn('birth_place');
            $table->dropColumn('nationality');
            $table->dropColumn('residence_address');
            $table->dropColumn('telephone_number');
            $table->dropColumn('photo');
            $table->dropColumn('valid_id');
        });
    }
}

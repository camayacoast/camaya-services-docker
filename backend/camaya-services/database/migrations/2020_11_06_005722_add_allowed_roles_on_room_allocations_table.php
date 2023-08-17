<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowedRolesOnRoomAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('camaya_booking_db')->table('room_allocations', function (Blueprint $table) {
            //
            $table->json('allowed_roles')->nullable()->after('entity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('camaya_booking_db')->table('room_allocations', function (Blueprint $table) {
            //
            $table->dropColumn('allowed_roles');
        });
    }
}

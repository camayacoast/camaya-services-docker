<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientSpousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_spouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
            $table->string('spouse_first_name');
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_last_name');
            $table->string('spouse_extension')->nullable();
            $table->string('spouse_gender');
            $table->date('spouse_birth_date');
            $table->string('spouse_birth_place');
            $table->string('spouse_citizenship');
            $table->string('spouse_government_issued_identification');
            $table->string('spouse_id_issuance_place');
            $table->date('spouse_id_issuance_date');
            $table->string('spouse_tax_identification_number');
            $table->string('spouse_occupation');
            $table->string('spouse_company_name');
            $table->string('spouse_company_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_spouses');
    }
}

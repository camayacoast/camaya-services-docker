<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientInformationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');

            $table->string('client_number')->nullable();
            
            $table->string('extension')->nullable();
            $table->string('contact_number');

            // BIS
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('gender')->nullable();

            $table->string('government_issued_id')->nullable();
            $table->string('government_issued_id_issuance_place')->nullable();
            $table->date('government_issued_id_issuance_date')->nullable();

            $table->string('tax_identification_number')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();

            $table->unsignedDecimal('monthly_household_income', 8, 2)->nullable();

            $table->string('home_phone')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('mobile_number')->nullable();

            $table->string('permanent_home_address')->nullable();
            $table->string('current_home_address')->nullable();
            $table->string('office_address')->nullable();
            $table->string('preferred_mailing_address')->nullable();

            $table->string('signature')->nullable();

            // Status
            $table->string('status')->nullable();

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
        Schema::dropIfExists('client_information');
    }
}

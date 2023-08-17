<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAmortizationSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            // Add new fields
            $table->decimal('generated_principal', $presition = 10, $scale = 2);
            $table->decimal('generated_interest', $presition = 10, $scale = 2);
            $table->decimal('generated_balance', $presition = 10, $scale = 2);
            $table->integer('is_old')->default(1);
            $table->integer('number')->nullable()->change();
            $table->integer('is_sales')->default(1);
            $table->integer('is_collection')->default(1);

            // Alter field's datatype
            $table->decimal('amount', $presition = 10, $scale = 2)->change();
            $table->decimal('amount_paid', $presition = 10, $scale = 2)->change();
            $table->decimal('principal', $presition = 10, $scale = 2)->change();
            $table->decimal('interest', $presition = 10, $scale = 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amortization_schedules', function (Blueprint $table) {
            $table->dropColumn('generated_principal');
            $table->dropColumn('generated_interest');
            $table->dropColumn('generated_balance');
            $table->dropColumn('number');
            $table->dropColumn('is_old');
            $table->dropColumn('is_sales');
            $table->dropColumn('is_collection')->default(1);


            // Alter field's datatype
            $table->decimal('amount', $presition = 8, $scale = 2)->change();
            $table->decimal('amount_paid', $presition = 8, $scale = 2)->change();
            $table->decimal('principal', $presition = 8, $scale = 2)->change();
            $table->decimal('interest', $presition = 8, $scale = 2)->change();
        });
    }
}

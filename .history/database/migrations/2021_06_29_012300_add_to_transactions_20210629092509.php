<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
            $table->string('pcf_date')->nullable();
            $table->string('pcf_letter')->nullable();
            $table->string('utilities_from')->nullable();
            $table->string('utilities_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
            $table->dropColumn('pcf_date');
            $table->dropColumn('pcf_letter');
            $table->dropColumn('utilities_from');
            $table->dropColumn('utilities_to');
        });
    }
}

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
            $table->float('total_gross')->nullable();
            $table->float('total_cwt')->nullable();
            $table->float('total_net')->nullable();
            $table->string('period_covered')->nullable();
            $table->string('prm_multiple_from')->nullable();
            $table->string('prm_multiple_to')->nullable();
            $table->string('cheque_date')->nullable();
            $table->float('gross_amount')->nullable();
            $table->float('witholding_tax')->nullable();
            $table->float('net_amount')->nullable();
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
            $table->dropColumn('total_gross');
            $table->dropColumn('total_cwt');
            $table->dropColumn('total_net');
            $table->dropColumn('period_covered');
            $table->dropColumn('rental_from');
            $table->dropColumn('rental_to');
            $table->dropColumn('cheque_date');
            $table->dropColumn('gross_amount');
            $table->dropColumn('witholding_tax');
            $table->dropColumn('net_amount');
        });
    }
}

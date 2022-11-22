<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTransactionsLeasing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('release_date')->nullable();
            $table->string('batch_no')->nullable();
            $table->float('amortization')->nullable();
            $table->float('interest')->nullable();
            $table->float('cwt')->nullable();
            $table->float('principal')->nullable();
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
            $table->$table->dropColumn('release_date');
            $table->$table->dropColumn('batch_no');
            $table->$table->dropColumn('amortization');
            $table->$table->dropColumn('interest');
            $table->$table->dropColumn('cwt');
            $table->$table->dropColumn('principal');
        });
    }
}

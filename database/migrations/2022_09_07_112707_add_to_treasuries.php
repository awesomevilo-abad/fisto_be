<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTreasuries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->string('bank_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_no')->nullable();
            $table->datetime('cheque_date',$precision=0)->nullable();
            $table->float('cheque_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->dropColumn('bank_id');
            $table->dropColumn('bank_name');
            $table->dropColumn('cheque_no');
            $table->dropColumn('cheque_date');
            $table->dropColumn('cheque_amount');
        });
    }
}

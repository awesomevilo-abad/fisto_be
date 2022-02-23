<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToChequeInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cheque_infos', function (Blueprint $table) {
            //
            $table->bigInteger('cheque_number')->nullable();
            $table->bigInteger('bank_id')->nullable();
            $table->string('due_date')->nullable();
            $table->float('cheque_amount')->nullable();
            $table->string('date_released')->nullable();
            $table->string('date_prepared')->nullable();
            $table->string('date_cleared')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cheque_infos', function (Blueprint $table) {
            //
            $table->dropColumn('reason_id');
            $table->dropColumn('remarks');
            $table->dropColumn('reason_id');
            $table->dropColumn('remarks');
            $table->dropColumn('reason_id');
            $table->dropColumn('remarks');
            $table->dropColumn('reason_id');
            $table->dropColumn('remarks');
        });
    }
}

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
            $table->string('bank_id')->nullable();
            $table->bigInteger('due_date')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
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
        });
    }
}

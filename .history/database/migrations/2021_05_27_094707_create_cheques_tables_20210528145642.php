<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cheque_tables', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cheque_info_id')->unsigned();
            $table->string('transaction_id');
            $table->timestamps();

            // CHEQUEINFO
            $table->foreign('cheque_info_id')->references('id')->on('cheque_infos')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cheque_tables');
    }
}

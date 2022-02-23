<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequeTablesTable extends Migration
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
            $table->bigInteger('transaction_id')->unsigned();
            $table->timestamps();

            // CHEQUE
            $table->foreign('cheque_info_id')->references('id')->on('cheque_clearings')->onDelete('cascade');

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

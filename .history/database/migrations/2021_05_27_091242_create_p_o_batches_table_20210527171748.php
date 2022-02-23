<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePOBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('p_o_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('po_no')->unsigned();
            $table->float('po_amount');
            $table->bigInteger('rr_id')->unsigned();
            $table->timestamps();

            // CHEQUEINFO
            $table->foreign('rr_id')->references('rr_id')->on('cheque_infos')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('p_o_batches');
    }
}

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
            $table->boolean('is_add')->nullable();
            $table->boolean('is_editable')->nullable();
            $table->string('po_no')->nullable();
            $table->float('po_amount')->nullable();
            $table->float('po_qty')->nullable();
            $table->float('previous_balance')->nullable();
            $table->float('remaining_balance')->nullable();
            $table->bigInteger('request_id')->nullable();
            $table->float('po_total_amount')->nullable();
            $table->timestamps();
            $table->softDeletes($column='deleted_at',$precision=0);
            

            // CHEQUEINFO
            // $table->foreign('rr_id')->references('id')->on('r_r_batches')->onDelete('cascade');

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

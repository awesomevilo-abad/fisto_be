<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debit_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("request_id")->nullable();
            $table->string("pn_no")->nullable();
            $table->string("interest_from")->nullable();
            $table->string("interest_to")->nullable();
            $table->string("outstanding_amount")->nullable();
            $table->string("interest_rate")->nullable();
            $table->string("no_of_days")->nullable();
            $table->string("principal_amount")->nullable();
            $table->string("interest_due")->nullable();
            $table->string("cwt")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debit_batches');
    }
}

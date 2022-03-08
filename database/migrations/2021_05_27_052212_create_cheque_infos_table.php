<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequeInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cheque_infos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cheque_number')->nullable();
            $table->bigInteger('bank_id')->nullable();
            $table->string('due_date')->nullable();
            $table->float('cheque_amount')->nullable();
            $table->string('date_released')->nullable();
            $table->string('date_prepared')->nullable();
            $table->string('date_cleared')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('cheque_infos');
    }
}

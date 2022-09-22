<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('tag_id');
            $table->date('date_received')->nullable();
            $table->string('receipt_type')->nullable();
            $table->float('percentage_tax')->nullable();
            $table->float('witholding_tax')->nullable();
            $table->float('net_amount')->nullable();
            $table->bigInteger('approver_id')->nullable();
            $table->string('approver_name')->nullable();
            $table->string('status');
            $table->date('date_status');
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
        Schema::dropIfExists('files');
    }
}

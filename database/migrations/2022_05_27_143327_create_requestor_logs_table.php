<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requestor_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('transaction_no');
            $table->string('description')->nullable();
            $table->string('status');
            $table->dateTime('date_status');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('reason_id')->nullable();
            $table->string('reason_description')->nullable();
            $table->string('reason_remarks')->nullable();
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
        Schema::dropIfExists('requestor_logs');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewMonitoringTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitoring', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('counter_id');
            $table->string('receipt_no');
            $table->string('status');
            $table->string('state');
            $table->string('receiver')->nullable();
            $table->bigInteger('notice_count')->nullable();
            $table->dateTime('latest_notice')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('monitoring');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransmit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmit', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('tag_id')->nullable();
            $table->date('date_received')->nullable();
            $table->string('status');
            $table->date('date_status');
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
        Schema::dropIfExists('transmit');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferrenceGroupBatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referrence_group_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('request_id')->nullable();
            $table->string('referrence_no')->nullable();
            $table->float('referrence_total_amount')->nullable();
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
        Schema::dropIfExists('referrence_group_batches');
    }
}

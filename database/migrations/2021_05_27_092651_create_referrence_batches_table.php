<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferrenceBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referrence_batches', function (Blueprint $table) {
            $table->id();
            $table->string('referrence_type')->nullable();
            $table->unsignedBigInteger('request_id');
            $table->string('referrence_no')->nullable();
            $table->float('referrence_amount')->nullable();
            $table->float('referrence_qty')->nullable();
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
        Schema::dropIfExists('referrence_batches');
    }
}

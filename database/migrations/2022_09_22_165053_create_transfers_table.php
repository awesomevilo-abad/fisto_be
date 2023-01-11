<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->string('process')->nullable();
            $table->unsignedBigInteger('tag_id');
            $table->bigInteger('from_distributed_id')->nullable();
            $table->string('from_distributed_name')->nullable();
            $table->bigInteger('to_distributed_id')->nullable();
            $table->string('to_distributed_name')->nullable();
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
        Schema::dropIfExists('transfers');
    }
}

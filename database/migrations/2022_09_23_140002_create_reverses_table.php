<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReversesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reverses', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('tag_id');
            $table->string('user_role')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('status');
            $table->date('date_status');
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
            $table->bigInteger('distributed_id')->nullable();
            $table->string('distributed_name')->nullable();
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
        Schema::dropIfExists('reverses');
    }
}

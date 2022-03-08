<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('return_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id');
            $table->date('date_received');
            $table->string('status');
            $table->date('date_status');
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
            $table->string('distributed_to')->nullable();
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
        Schema::dropIfExists('return_vouchers');
    }
}

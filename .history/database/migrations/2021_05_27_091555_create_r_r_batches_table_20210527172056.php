<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRRBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('r_r_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rr_no')->unsigned();
            $table->float('date_recrreived');
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
        Schema::dropIfExists('r_r_batches');
    }
}

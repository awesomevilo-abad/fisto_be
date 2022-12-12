<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClearingAccountTitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clearing_account_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clear_id')->nullable();
            $table->string('entry')->nullable();
            $table->unsignedBigInteger('account_title_id');
            $table->string('account_title_name');
            $table->float('amount');
            $table->string('remarks')->nullable();
            $table->string('transaction_type')->nullable();
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
        Schema::dropIfExists('clearing_account_titles');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_numbers', function (Blueprint $table) {
            $table->id();
            $table->string("category")->nullable();
            $table->bigInteger("supplier_id")->nullable();
            $table->bigInteger("location_id")->nullable();
            $table->string("account_no")->nullable();
            $table->boolean("is_active")->nullable();
            $table->timestamps();
            
            $table->foreign("supplier_id")->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_numbers');
    }
}

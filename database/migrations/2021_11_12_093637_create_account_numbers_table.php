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
            $table->string("account_no")->nullable();
            $table->unsignedBigInteger("location_id");
            $table->unsignedBigInteger("category_id");
            $table->unsignedBigInteger("supplier_id");
            $table->timestamps();
            $table->softDeletes($column='deleted_at',$precision=0);
            
            $table->foreign("location_id")->references('id')->on('locations')->onDelete('cascade');
            $table->foreign("category_id")->references('id')->on('categories')->onDelete('cascade');
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

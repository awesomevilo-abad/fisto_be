<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCounterReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('counter_receipts', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_countered')->nullable();
            $table->dateTime('date_transaction')->nullable();
            $table->string('counter_receipt_no')->nullable();
            $table->string('receipt_type')->nullable();
            $table->string('receipt_no')->nullable();
            $table->bigInteger('supplier_id')->nullable();
            $table->string('supplier')->nullable();
            $table->bigInteger('department_id')->nullable();
            $table->string('department')->nullable();
            $table->float('amount')->nullable();
            $table->string('status')->nullable();
            $table->string('state')->nullable();
            $table->string('receiver')->nullable();
            $table->longText('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('counter_receipts');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('users_id');
            $table->string('i');
            $table->bigInteger('transaction_id')->unsigned();
            $table->bigInteger('tag_id');
            $table->bigInteger('document_id');
            $table->dateTime('document_date');
            $table->bigInteger('category_id');
            $table->bigInteger('company_id');
            $table->bigInteger('supplier_id');
            $table->bigInteger('po_id');
            $table->bigInteger('referrence_id')->unsigned();
            $table->dateTime('date_requested');
            $table->string('remarks');
            $table->bigInteger('reason_id');
            $table->bigInteger('document_no');
            $table->float('document_amount');

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
        Schema::dropIfExists('transactions');
    }
}

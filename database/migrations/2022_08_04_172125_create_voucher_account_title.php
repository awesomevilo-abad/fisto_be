<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoucherAccountTitle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_account_title', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('associate_id')->nullable();
            $table->unsignedBigInteger('treasury_id')->nullable();
            $table->string('entry')->nullable();
            $table->unsignedBigInteger('account_title_id');
            $table->string('account_title_name');
            $table->float('amount');
            $table->string('remarks')->nullable();
            $table->string('transaction_type')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_account_title');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('tagging_tag_id')->nullable();
            $table->string('utilities_category')->nullalbe();
            $table->string('utilities_account_no')->nullalbe();
            $table->float('utilities_consumption')->nullalbe();
            $table->string('utilities_uom')->nullalbe();
            $table->string('utilities_receipt_no')->nullalbe();
            $table->json('payroll_client')->nullalbe();
            $table->string('payroll_category')->nullalbe();
            $table->string('payroll_type')->nullalbe();
            $table->string('payroll_from')->nullalbe();
            $table->string('payroll_to')->nullalbe();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('tagging_tag_id');
            $table->dropColumn('utilities_category');
            $table->dropColumn('utilities_account_no');
            $table->dropColumn('utilities_consumption');
            $table->dropColumn('utilities_uom');
            $table->dropColumn('utilities_receipt_no');
            $table->dropColumn('payroll_client');
            $table->dropColumn('payroll_category');
            $table->dropColumn('payroll_type');
            $table->dropColumn('payroll_from');
            $table->dropColumn('payroll_to');
        });
    }
}

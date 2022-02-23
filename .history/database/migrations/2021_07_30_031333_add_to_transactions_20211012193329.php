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
            $table->bigInteger('tagging_tag_id')->nullalbe();
            $table->string('utilities_category')->nullalbe();
            $table->string('utilities_account_no')->nullalbe();
            $table->bigInteger('utilities_consumption')->nullalbe();
            $table->bigInteger('utilities_uom')->nullalbe();
            $table->bigInteger('utilities_receipt_no')->nullalbe();
            $table->bigInteger('payroll_location')->nullalbe();
            $table->bigInteger('payroll_tax_category')->nullalbe();
            $table->bigInteger('payroll_from')->nullalbe();
            $table->bigInteger('payroll_to')->nullalbe();
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
            $table->dropColumn('utilities_account_no');
            $table->dropColumn('utilities_consumption');
            $table->dropColumn('utilities_uom');
            $table->dropColumn('utilities_receipt_no');
            $table->dropColumn('payroll_location');
            $table->dropColumn('payroll_tax_category');
            $table->dropColumn('payroll_from');
            $table->dropColumn('payroll_to');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->float('balance_document_po_amount')->nullable();
            $table->float('balance_document_ref_amount')->nullable();
            $table->float('balance_po_ref_amount')->nullable();
            $table->float('balance_po_ref_qty')->nullable();
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
            $table->dropColumn('balance_document_po_amount');
            $table->dropColumn('balance_document_ref_amount');
            $table->dropColumn('balance_po_ref_amount');
            $table->dropColumn('balance_po_ref_amount');
        });
    }
}

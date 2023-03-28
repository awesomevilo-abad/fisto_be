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
            $table->string('id_prefix');
            $table->bigInteger('id_no');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('department');
            $table->string('transaction_id');
            $table->bigInteger('tag_id')->unique();
            $table->bigInteger('document_id')->nullable();
            $table->string('capex_no')->nullable();
            $table->string('document_type')->nullable();
            $table->dateTime('document_date')->nullable();
            $table->bigInteger('category_id')->nullable();
            $table->string('category')->nullable();
            $table->bigInteger('company_id');
            $table->string('company');
            $table->bigInteger('supplier_id');
            $table->string('supplier');
            $table->float('po_total_amount')->nullable();
            $table->float('po_total_qty')->nullable();
            $table->float('rr_total_qty')->nullable();
            $table->float('referrence_total_amount')->nullable();
            $table->float('referrence_total_qty')->nullable();
            $table->dateTime('date_requested');
            $table->string('remarks')->nullable();
            $table->string('payment_type');
            $table->string('status');
            $table->bigInteger('reason_id')->nullable();
            $table->string('reason')->nullable();
            $table->string('reason_remarks')->nullable();
            $table->string('document_no')->nullable();
            $table->float('document_amount')->nullable();
            $table->string('pcf_name')->nullable();
            $table->string('pcf_branch')->nullable();
            $table->string('pcf_date')->nullable();
            $table->string('pcf_letter')->nullable();
            $table->string('utilities_from')->nullable();
            $table->string('utilities_to')->nullable();

            $table->float('balance_document_po_amount')->nullable();
            $table->float('balance_document_ref_amount')->nullable();
            $table->float('balance_po_ref_amount')->nullable();
            $table->float('balance_po_ref_qty')->nullable();
            
            $table->bigInteger('tag_no')->nullable();
            $table->string('voucher_no')->nullable();
            $table->dateTime('voucher_month', $precision = 0);
            $table->bigInteger('utilities_category_id')->nullable();
            $table->string('utilities_category')->nullable();
            $table->bigInteger('utilities_account_no_id')->nullable();
            $table->string('utilities_account_no')->nullable();
            $table->float('utilities_consumption')->nullable();
            $table->bigInteger('utilities_location_id')->nullable();
            $table->string('utilities_location')->nullable();
            $table->string('utilities_receipt_no')->nullable();
            $table->json('payroll_client')->nullable();
            $table->string('payroll_category')->nullable();
            $table->string('payroll_type')->nullable();
            $table->string('payroll_from')->nullable();
            $table->string('payroll_to')->nullable();
            
            $table->string('referrence_type')->nullable();
            $table->string('referrence_no')->nullable();
            $table->float('referrence_amount')->nullable();
            $table->float('referrence_qty')->nullable();
            $table->bigInteger('referrence_id')->nullable();
            $table->bigInteger('distributed_id')->nullable();
            $table->string('distributed_name')->nullable();
            $table->bigInteger('approver_id')->nullable();
            $table->string('approver_name')->nullable();
            $table->bigInteger('reverse_distributed_id')->nullable();
            $table->string('reverse_distributed_name')->nullable();

            // $table->timestamps();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            // TAGGINGS
            // $table->foreign('transaction_id')->references('transaction_id')->on('taggings')->onDelete('cascade');

            // // GASES
            // $table->foreign('tag_id')->references('tag_id')->on('filings')->onDelete('cascade');

            // // ASSOCIATES
            // $table->foreign('tag_id')->references('tag_id')->on('associates')->onDelete('cascade');

            // // SPECIALISTS
            // $table->foreign('tag_id')->references('tag_id')->on('specialists')->onDelete('cascade');

            // // MATCHES
            // $table->foreign('tag_id')->references('tag_id')->on('matches')->onDelete('cascade');

            // // RETURN VOUCHERS
            // $table->foreign('tag_id')->references('tag_id')->on('return_vouchers')->onDelete('cascade');

            // // APPROVERS
            // $table->foreign('tag_id')->references('tag_id')->on('approvers')->onDelete('cascade');

            // // CHEQUECREATIONS
            // $table->foreign('tag_id')->references('tag_id')->on('cheque_creations')->onDelete('cascade');

            // // TREASURIES
            // $table->foreign('tag_id')->references('tag_id')->on('treasuries')->onDelete('cascade');

            // // CHEQUERELEASEDS
            // $table->foreign('tag_id')->references('tag_id')->on('cheque_releaseds')->onDelete('cascade');

            // // CHEQUECLEARINGS
            // $table->foreign('tag_id')->references('tag_id')->on('cheque_clearings')->onDelete('cascade');

            // // CHEQUE
            // $table->foreign('transaction_id')->references('transaction_id')->on('cheque_tables')->onDelete('cascade');

            // ------------- SUBTABLE TRANSACTION

            // // POBATCH
            // $table->foreign('po_id')->references('id')->on('p_o_batches')->onDelete('cascade');

            // // REFERRENCEBATCH
            // $table->foreign('referrence_id')->references('id')->on('referrence_batches')->onDelete('cascade');

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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToReturnVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('return_vouchers', function (Blueprint $table) {
            //
            $table->bigInteger('reason_id')->nullable();
            $table->string('remarks')->nullable();
            $table->string('distributed_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('return_vouchers', function (Blueprint $table) {
            //
            $table->dropColumn('reason_id');
            $table->dropColumn('remarks');
            $table->dropColumn('distributed_to');
        });
    }
}

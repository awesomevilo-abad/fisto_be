<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToDstToDebitBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debit_batches', function (Blueprint $table) {
            //
            $table->float('dst')->nullable()->after('cwt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debit_batches', function (Blueprint $table) {
            //
            $table->$table->dropColumn('dst');
        });
    }
}

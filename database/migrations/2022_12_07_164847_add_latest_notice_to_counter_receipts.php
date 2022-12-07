<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatestNoticeToCounterReceipts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('counter_receipts', function (Blueprint $table) {
            //
            $table->datetime('latest_notice')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('counter_receipts', function (Blueprint $table) {
            //
            $table->dropColumn('latest_notice');
        });
    }
}

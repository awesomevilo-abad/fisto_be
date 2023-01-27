<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToMonitoringNotice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('_monitoring', function (Blueprint $table) {
            //
            $table->string('counter_receipt_status')->nullable();
            $table->integer('notice_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monitoring', function (Blueprint $table) {
            //
            $table->dropColumn('counter_receipt_status');
            $table->dropColumn('notice_count');
        });
    }
}

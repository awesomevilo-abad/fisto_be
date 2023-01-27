<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToMonitoring extends Migration
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
            $table->bigInteger('reason_id')->nullable();
            $table->string('reason')->nullable();
            $table->string('reason_remarks')->nullable();
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
            $table->dropColumn('reason_id');
            $table->dropColumn('reason');
            $table->dropColumn('reason_remarks');
        });
    }
}

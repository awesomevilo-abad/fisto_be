<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BankTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(DB::table('banks')->count() > 0){
            return "Table is not Empty, Therefore NOT";
        }

        DB::table('banks')->insert([
            [
                "bank_code" =>"10004",
                "bank_name" =>"BDO",
                "bank_account" =>"BDO",
                "bank_location" =>,
                "is_active" =>,
            ]
        ]);


    }
}

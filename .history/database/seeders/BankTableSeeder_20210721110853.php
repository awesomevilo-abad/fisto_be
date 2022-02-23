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
                "bank_account" =>"BDO-2356-0235-1023",
                "bank_location" =>"Angeles, Pampanga",
                "is_active" =>1,
            ],
            [
                "bank_code" =>"10005",
                "bank_name" =>"BPI",
                "bank_account" =>"BPI-1-2356-0235-1023",
                "bank_location" =>"Angeles, Pampanga",
                "is_active" =>1,
            ]
        ]);


    }
}

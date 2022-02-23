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
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "bank_code" =>"10005",
                "bank_name" =>"BPI",
                "bank_account" =>"BPI-1-56-0235-1023",
                "bank_location" =>"San Fernando, Pampanga",
                "is_active" =>1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "bank_code" =>"10006",
                "bank_name" =>"Metrobank",
                "bank_account" =>"MBTC-21335-1023",
                "bank_location" =>"San Fernando, Pampanga",
                "is_active" =>1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);


    }
}

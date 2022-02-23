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
                "bank_code" =>,
                "bank_code" =>,
            ]
        ]);


    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReferrenceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(DB::table('referrences')->count()>0){
            return "Table is not Empty, Therefore NOT";
        }

        DB::table('referrences')->insert([
            [
                "referrence_type" => "CR",
                "referrence_desccription" => "CR",
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);
    }
}

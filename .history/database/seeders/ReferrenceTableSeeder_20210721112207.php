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
                "referrence_desccription" => "Custom Report",
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "referrence_type" => "SI",
                "referrence_desccription" => "Sales Invoice",
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "referrence_type" => "OR",
                "referrence_desccription" => "Official Report",
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "referrence_type" => "DR",
                "referrence_desccription" => "Delivery Report",
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(DB::table('supplier_type')->count() > 0 ){
            return "Table is not Empty, Therefore NOT";
        }

        DB::table('supplier_types')->insert([
            [
                "type" => "Priority",
                "transaction_days" =>1,
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "type" => "Rush",
                "transaction_days" =>1,
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "type" => "Priority",
                "transaction_days" =>1,
                "is_active" => 1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);
    }
}

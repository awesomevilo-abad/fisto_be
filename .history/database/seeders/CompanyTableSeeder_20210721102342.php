<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompanyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if(DB::table('categories')->count() == 0){

            DB::table('categories')->insert([
                [
                    "name" => "rentals",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "loans",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "coop loans and dues",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "cash flow for new store",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "meat entry fee",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "comission",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "leasing",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "funds",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "payment for supplier",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "government benefits",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "billing",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "salaries of physican and dentist",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "maternity leave",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "mancom fund",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "garbage disposal",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "down payment",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "confidential request",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "progress billing",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "100% payment",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "retention",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ],
                [
                    "name" => "additional works",
                    "is_active" => 1,
                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ]

            ]);

        } else { echo "Table is not empty, therefore NOT "; }
    }
}

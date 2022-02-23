<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Category::factory()->times(100)->create();
        if(DB::table('categories')->count() == 0){

            DB::table('categories')->insert([
                [
                    "name" => "rentals",
                    "is_active" => 1
                ],
                [
                    "name" => "loans",
                    "is_active" => 1
                ],
                [
                    "name" => "coop loans and dues",
                    "is_active" => 1
                ],
                [
                    "name" => "cash flow for new store",
                    "is_active" => 1
                ],
                [
                    "name" => "meat entry fee",
                    "is_active" => 1
                ],
                [
                    "name" => "comission",
                    "is_active" => 1
                ],
                [
                    "name" => "leasing",
                    "is_active" => 1
                ],
                [
                    "name" => "funds",
                    "is_active" => 1
                ],
                [
                    "name" => "payment for supplier",
                    "is_active" => 1
                ],
                [
                    "name" => "government benefits",
                    "is_active" => 1
                ],
                [
                    "name" => "billing",
                    "is_active" => 1
                ],
                [
                    "name" => "salaries of physican and dentist",
                    "is_active" => 1
                ],
                [
                    "name" => "maternity leave",
                    "is_active" => 1
                ],
                [
                    "name" => "mancom fund",
                    "is_active" => 1
                ][
                    "name" => "garbage disposal",
                    "is_active" => 1
                ],
                [
                    "name" => "down payment",
                    "is_active" => 1
                ],
                [
                    "name" => "confidential request",
                    "is_active" => 1
                ],
                [
                    "name" => "progress billing",
                    "is_active" => 1
                ],
                [
                    "name" => "100% payment",
                    "is_active" => 1
                ],
                [
                    "name" => "retention",
                    "is_active" => 1
                ],
                [
                    "name" => "additional works",
                    "is_active" => 1
                ]

            ]);

        } else { echo "Table is not empty, therefore NOT "; }


    }
}

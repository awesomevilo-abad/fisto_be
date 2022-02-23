<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

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
        if(DB::table('users')->count() == 0){

            DB::table('users')->insert([


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
            ]

            ]);

        } else { echo "\e[31mTable is not empty, therefore NOT "; }


    }
}

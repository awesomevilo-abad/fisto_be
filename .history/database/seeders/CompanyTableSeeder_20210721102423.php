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
                    "company_code" => "additional works",
                    "company_code" => "additional works",
                    "is_active" => 1,

                    "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                    "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
                ]

            ]);

        } else { echo "Table is not empty, therefore NOT "; }
    }
}

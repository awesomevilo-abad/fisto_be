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
                ""
            ]
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Reason;

class ReasonTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        if(DB::table('reasons')->count()>0){
            return "Table is not Empty, Therefore NOT";
        }


        DB::table('reasons')->insert([
            [
                "reason"  => "Incomplete Credentials",
                "remarks" => "Incomplete Credentials of Tagging Request",
                "is_active"=>1,
                "created_at"=>Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at"=>Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                "reason" => "Invalid Details",
                "remarks" => "Receipt Details are invalid",
                "is_active" =>1
            ]
        ]);
    }
}

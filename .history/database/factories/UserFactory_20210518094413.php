<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id_prefix'=>'RDFFLFI'
            ,'id_no'=>$this->faker->phoneNumber
            ,'role'=>$this->faker->jobTitle
            ,'first_name'=>$this->faker->firstName
            ,'middle_name'=>$this->faker->firstName
            ,'last_name'=>$this->faker->firstName
            ,'suffix'=>$this->faker->suffix
            ,'department'=>$this->faker->department
            ,'position'=>$this->faker->jobTitle
            ,'permissions'=>[
                "Tagging of Request",
                "Tagging Document Reports",
                "Releasing of Cheque",
                "Cheque Reports",
                "Tagging of Document"
            ]
            ,'document_types'=>
            ,'username'=>
            ,'password'=>
            ,'is_active'=>
            ,'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' // password
            ,'remember_token' => Str::random(10)
        ];
    }
}

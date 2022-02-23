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
            'id_prefix'=>
            ,'id_no'=>
            ,'role'=>
            ,'first_name'=>
            ,'middle_name'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'id_no'=>
            ,'is_active'=>
            'name' => $this->faker->name,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }
}

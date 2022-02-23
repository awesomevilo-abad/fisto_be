<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            [
                {
                  "name": "rentals",
                  "is_active": 1
                },
                {
                  "name": "loans",
                  "is_active": 1
                },
                {
                  "name": "coop loans and dues",
                  "is_active": 1
                },
                {
                  "name": "cash flow for new store",
                  "is_active": 1
                },
                {
                  "name": "meat entry fee",
                  "is_active": 1
                },
                {
                  "name": "comission",
                  "is_active": 1
                },
                {
                  "name": "leasing",
                  "is_active": 1
                },
                {
                  "name": "funds",
                  "is_active": 1
                },
                {
                  "name": "payment for supplier",
                  "is_active": 1
                },
                {
                  "name": "government benefits",
                  "is_active": 1
                },
                {
                  "name": "billing",
                  "is_active": 1
                },
                {
                  "name": "salaries of physican and dentist",
                  "is_active": 1
                },
                {
                  "name": "maternity leave",
                  "is_active": 1
                },
                {
                  "name": "mancom fund",
                  "is_active": 1
                },
                {
                  "name": "garbage disposal",
                  "is_active": 1
                },
                {
                  "name": "down payment",
                  "is_active": 1
                },
                {
                  "name": "confidential request",
                  "is_active": 1
                },
                {
                  "name": "progress billing",
                  "is_active": 1
                },
                {
                  "name": "100% payment",
                  "is_active": 1
                },
                {
                  "name": "retention",
                  "is_active": 1
                },
                {
                  "name": "additional works",
                  "is_active": 1
                }
              ]
        ];
    }
}

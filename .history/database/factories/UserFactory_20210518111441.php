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
            {
                "id_prefix": "RDFFLFI",
                "id_no": "10194",
                "role": "Requestor",
                "first_name": "Chito",
                "middle_name": "Sample",
                "last_name": "Dungo",
                "suffix": null,
                "department": "Asset Management Group",
                "position": "AMG Associate",
                "permissions": [ "Tagging of Request","Tagged Document Reports","Releasing of Cheque","Cheque Reports","Tagging of Document"],
                "document_types":[{"document_id":1,"category_id":1},{"document_id":1,"category_id":2},{"document_id":1,"category_id":3},{"document_id":2,"category_id":4},{"document_id":2,"category_id":5},{"document_id":2,"category_id":6},{"document_id":3,"category_id":0},{"document_id":4,"category_id":0}],
                "username": "cdungo",
                "password": "cdungo",
                "password_confirmation": "cdungo",
                "is_active": 1
            },
        ];
    }
}

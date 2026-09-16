<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = \App\Models\Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'api_key' => Str::random(32),
        ];
    }
}

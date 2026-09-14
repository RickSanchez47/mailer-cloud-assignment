<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFactory extends Factory
{
    protected $model = \App\Models\Form::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
        ];
    }
}

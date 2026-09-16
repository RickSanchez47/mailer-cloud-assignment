<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FormVersionFactory extends Factory
{
    protected $model = \App\Models\FormVersion::class;

    public function definition(): array
    {
        return [
            'version_number' => 1,
            'status' => 'draft',
            'schema' => ['fields' => []],
        ];
    }
}

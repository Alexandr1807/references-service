<?php

namespace Database\Factories;

use App\Models\BudgetHolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetHolder>
 */
class BudgetHolderFactory extends Factory
{
    protected $model = BudgetHolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tin'         => $this->faker->numerify('#########'),
            'name'        => $this->faker->company(),
            'region'      => $this->faker->state(),
            'district'    => $this->faker->citySuffix(),
            'address'     => $this->faker->streetAddress(),
            'phone'       => $this->faker->phoneNumber(),
            'responsible' => $this->faker->name(),
            'created_by'  => User::first()->id,
            'updated_by'  => User::first()->id,
        ];
    }
}

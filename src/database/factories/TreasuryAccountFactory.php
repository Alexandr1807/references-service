<?php

namespace Database\Factories;

use App\Models\TreasuryAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TreasuryAccount>
 */
class TreasuryAccountFactory extends Factory
{
    protected $model = TreasuryAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account'     => $this->faker->numerify('####################'),
            'mfo'         => $this->faker->numerify('#####'),
            'name'        => $this->faker->company(),
            'department'  => $this->faker->companySuffix(),
            'currency'    => $this->faker->randomElement(['USD','EUR','UZS','RUB']),
            'created_by'  => User::first()->id,
            'updated_by'  => User::first()->id,
        ];
    }
}

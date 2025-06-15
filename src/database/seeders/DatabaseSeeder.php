<?php

namespace Database\Seeders;

use App\Models\BudgetHolder;
use App\Models\TreasuryAccount;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);

        BudgetHolder::factory()->count(10000)->create();

        TreasuryAccount::factory()->count(10000)->create();
    }
}

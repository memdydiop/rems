<?php

namespace Database\Seeders;

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
        //User::factory(10)->create();

        $ghost = User::factory()->create([
            'name' => 'Ghost User',
            'email' => 'ghost@user.com',
        ]);

        $this->call([
            RolesSeeder::class,
            PlanSeeder::class,
        ]);

        // Assign Ghost Role to Ghost User
        $ghost->assignRole('Ghost');
    }
}

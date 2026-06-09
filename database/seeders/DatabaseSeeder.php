<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CoreParentAccountsSeeder::class,
            TenantPermissionsSeeder::class,
            VisaStatusSeeder::class,
            LegalCaseStatusSeeder::class,
        ]);

        // Factories rely on fakerphp/faker (require-dev). On production deployments
        // with --no-dev, skip demo/test user factory seeding.
        if (! app()->environment('production') && class_exists(\Faker\Factory::class)) {
            \App\Models\User::factory(10)->create();

            \App\Models\User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => '123456',
                'email_verified_at' => now(),
            ],
        );

        $this->call(TimezoneSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(StateSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(EmailTypeSeeder::class);
        $this->call(EditorSettingSeeder::class);
    }
}

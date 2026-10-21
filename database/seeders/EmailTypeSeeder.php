<?php

namespace Database\Seeders;

use App\Enums\Master\Status;
use App\Models\Master\EmailType;
use Illuminate\Database\Seeder;

class EmailTypeSeeder extends Seeder
{
    /**
     * Seed the application's default email types.
     */
    public function run(): void
    {
        $types = [
            ['code' => EmailType::CODE_REGISTRATION, 'name' => 'Registration', 'sort_order' => 1],
            ['code' => EmailType::CODE_FORGOT_PASSWORD, 'name' => 'Forgot Password', 'sort_order' => 2],
            ['code' => EmailType::CODE_RESET_PASSWORD, 'name' => 'Reset Password', 'sort_order' => 3],
            ['code' => EmailType::CODE_NOTIFICATION, 'name' => 'Notification', 'sort_order' => 4],
            ['code' => EmailType::CODE_SLA_ESCALATION, 'name' => 'SLA Escalation', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            EmailType::query()->firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => null,
                    'status' => Status::Active,
                    'sort_order' => $type['sort_order'],
                ],
            );
        }
    }
}

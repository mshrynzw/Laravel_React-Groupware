<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $group = Group::firstOrCreate(
            ['name' => '開発グループ'],
            ['description' => '初期データ']
        );

        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPERADMIN,
                'email_verified_at' => now(),
                'primary_group_id' => $group->id,
            ]
        );

        $superadmin->groups()->sync([$group->id]);

        if (User::count() < 6) {
            User::factory(5)->create();
        }
    }
}

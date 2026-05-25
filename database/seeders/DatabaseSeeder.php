<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Administrator role with full permissions',
        ]);

        $supervisorRole = Role::create([
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
            'description' => 'Regular supervisor role with limited permissions',
        ]);

        $satpamRole = Role::create([
            'name' => 'satpam',
            'display_name' => 'Satpam',
            'description' => 'Satpam role with specific permissions',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->addRole($adminRole);

        $supervisor = User::create([
            'name' => 'Supervisor User',
            'email' => 'supervisor@example.com',
            'password' => bcrypt('password'),
        ]);
        $supervisor->addRole($supervisorRole);

        $satpam = User::create([
            'name' => 'Satpam User',
            'email' => 'satpam@example.com',
            'password' => bcrypt('password'),
        ]);
        $satpam->addRole($satpamRole);
    }
}


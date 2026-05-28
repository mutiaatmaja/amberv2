<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Schedule;
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
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Administrator role with full permissions',
        ]);

        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], [
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
            'description' => 'Regular supervisor role with limited permissions',
        ]);

        $satpamRole = Role::firstOrCreate(['name' => 'satpam'], [
            'name' => 'satpam',
            'display_name' => 'Satpam',
            'description' => 'Satpam role with specific permissions',
        ]);

        $admin = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        if (! $admin->hasRole('admin')) {
            $admin->addRole($adminRole);
        }

        $supervisor = User::updateOrCreate([
            'email' => 'supervisor@example.com',
        ], [
            'name' => 'Supervisor User',
            'email' => 'supervisor@example.com',
            'password' => 'password',
        ]);

        if (! $supervisor->hasRole('supervisor')) {
            $supervisor->addRole($supervisorRole);
        }

        $dayShift = [
            'checkin_time' => '08:30',
            'checkout_time' => '20:30',
            'patrol_1_time' => '15:00',
            'standby_1_time' => '11:00',
            'patrol_2_time' => '19:30',
            'standby_2_time' => '16:00',
        ];

        $nightShift = [
            'checkin_time' => '20:30',
            'checkout_time' => '08:30',
            'patrol_1_time' => '02:00',
            'standby_1_time' => '23:00',
            'patrol_2_time' => '04:00',
            'standby_2_time' => '01:00',
        ];

        $satpamProfiles = [
            ['name' => 'Fadil1', 'email' => 'fadil1@example.com', 'schedule' => $dayShift],
            ['name' => 'Sarbani2', 'email' => 'sarbani2@example.com', 'schedule' => $nightShift],
            ['name' => 'Febripagi', 'email' => 'febripagi@example.com', 'schedule' => $dayShift],
            ['name' => 'Febrimalam', 'email' => 'febrimalam@example.com', 'schedule' => $nightShift],
            ['name' => 'Fadil2', 'email' => 'fadil2@example.com', 'schedule' => $nightShift],
            ['name' => 'Sarbani1', 'email' => 'sarbani1@example.com', 'schedule' => $dayShift],
            ['name' => 'Satpam User', 'email' => 'satpam@example.com', 'schedule' => $dayShift],
        ];

        foreach ($satpamProfiles as $profile) {
            $satpam = User::updateOrCreate([
                'email' => $profile['email'],
            ], [
                'name' => $profile['name'],
                'email' => $profile['email'],
                'password' => 'password',
            ]);

            if (! $satpam->hasRole('satpam')) {
                $satpam->addRole($satpamRole);
            }

            $schedule = $profile['schedule'];

            Schedule::updateOrCreate([
                'user_id' => $satpam->id,
            ], [
                'checkin_time' => $schedule['checkin_time'],
                'checkout_time' => $schedule['checkout_time'],
                'patrol_1_time' => $schedule['patrol_1_time'],
                'standby_1_time' => $schedule['standby_1_time'],
                'patrol_2_time' => $schedule['patrol_2_time'],
                'standby_2_time' => $schedule['standby_2_time'],
                'patrol_a_time' => $schedule['patrol_1_time'],
                'patrol_b_time' => $schedule['standby_1_time'],
                'patrol_c_time' => $schedule['patrol_2_time'],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Super Admin users
        $superAdmins = [
            [
                'first_name' => 'Samuel',
                'last_name' => 'Kiraelu',
                'name' => 'Samuel Kiraelu',
                'email' => 'samuelkiiraeluk@gmail.com',
                'password' => 'Samuel@13',
                'phone' => '+256712345678',
                'country_code' => '+256',
                'is_active' => true,
            ],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'name' => 'Super Admin',
                'email' => 'superadmin@lafab.com',
                'password' => 'Admin@1234',
                'phone' => '+256734567890',
                'country_code' => '+256',
                'is_active' => true,
            ],
        ];

        foreach ($superAdmins as $adminData) {
            $user = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'name' => $adminData['name'],
                    'password' => Hash::make($adminData['password']),
                    'phone' => $adminData['phone'],
                    'country_code' => $adminData['country_code'],
                    'is_active' => $adminData['is_active'],
                    'email_verified_at' => now(),
                ]
            );
            
            // Assign super_admin role
            $user->assignRole('super_admin');
            $this->command->info("Super Admin created: {$user->email}");
        }

        $additionalUsers = [
            // Platform admin
            [
                'first_name' => 'Platform',
                'last_name' => 'Admin',
                'email' => 'platform@stardena.com',
                'password' => 'Platform@123',
                'phone' => '+256700000001',
                'role' => 'platform_admin',
                'is_active' => true,
            ],
            // Compliance officer
            [
                'first_name' => 'Compliance',
                'last_name' => 'Officer',
                'email' => 'compliance@stardena.com',
                'password' => 'Compliance@123',
                'phone' => '+256700000002',
                'role' => 'compliance_officer',
                'is_active' => true,
            ],
            // Merchant admin (sample merchant)
            [
                'first_name' => 'Merchant',
                'last_name' => 'Admin',
                'email' => 'merchant@stardena.com',
                'password' => 'Merchant@123',
                'phone' => '+256700000003',
                'role' => 'merchant_admin',
                'is_active' => true,
            ],
            // Merchant operator
            [
                'first_name' => 'Merchant',
                'last_name' => 'Operator',
                'email' => 'operator@stardena.com',
                'password' => 'Operator@123',
                'phone' => '+256700000004',
                'role' => 'merchant_operator',
                'is_active' => true,
            ],
            // Merchant viewer
            [
                'first_name' => 'Merchant',
                'last_name' => 'Viewer',
                'email' => 'viewer@stardena.com',
                'password' => 'Viewer@123',
                'phone' => '+256700000005',
                'role' => 'merchant_viewer',
                'is_active' => true,
            ],
        ];

        foreach ($additionalUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'country_code' => '+256',
                    'is_active' => $userData['is_active'],
                    'email_verified_at' => now(),
                ]
            );
            
            // Assign the specified role
            $user->assignRole($userData['role']);
            $this->command->info("User created: {$user->email} as {$userData['role']}");
        }

        $this->command->info('Users seeded successfully! Total users: ' . User::count());
    }
}
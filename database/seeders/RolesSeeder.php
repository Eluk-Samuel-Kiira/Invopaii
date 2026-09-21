<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $rolePermissions = [
            'super_admin' => '*',

            'platform_admin' => [
                // Reference data — view only
                'view countries', 'view currencies', 'view exchange rates',

                // Providers
                'view providers', 'create providers', 'edit providers',
                'view routing rules', 'manage routing rules',

                // Fee schedules — view only
                'view fee schedules',

                // Companies
                'view companies', 'create companies', 'edit companies',
                'approve companies', 'reject companies', 'suspend companies',

                // Compliance
                'view company documents', 'manage company documents',
                'run verification checks',
                'view risk assessments', 'manage risk rules',
                'manage blocklist', 'manage velocity limits',
                'view company compliance',
                'view company documents', 'manage company documents',
                'view company representatives',
                'run verification checks',

                // Disputes
                'view disputes', 'manage disputes',
                'manage dispute evidence', 'submit dispute evidence',

                // Cross-merchant
                'view all payments', 'view all refunds', 'view all payouts',
                'approve all payouts',
                'view all invoices', 'view all customers', 'view all subscriptions',
                'view ledger', 'view reconciliation',

                // Users (no role/permission edits)
                'view users', 'create users', 'edit users',
                'view roles', 'view permissions',

                // System
                'view audit logs', 'view api request logs', 'view webhook events',
                'manage announcements', 'view exports', 'create exports',
                'view system health',
            ],

            'compliance_officer' => [
                'view companies', 'view company compliance', 'view company representatives',
                'approve companies', 'reject companies', 'suspend companies',
                'view company documents', 'manage company documents',
                'run verification checks',
                'view risk assessments', 'manage risk rules',
                'manage blocklist', 'manage velocity limits',
                'view disputes', 'manage disputes',
                'manage dispute evidence', 'submit dispute evidence',
                'view all payments', 'view all customers',
                'view audit logs',
            ],

            'merchant_admin' => [
                // Own company
                'view own company', 'edit own company',
                'view company team', 'manage company team',
                'invite team members', 'remove team members',

                // Onboarding
                'submit company verification',
                'view company bank accounts', 'manage company bank accounts',
                'view company documents', 'manage company documents',
                'view company representatives', 'manage company representatives',

                // Developers
                'view api keys', 'create api keys', 'revoke api keys',
                'view webhooks', 'create webhooks', 'edit webhooks', 'delete webhooks',
                'view webhook deliveries', 'replay webhook deliveries',

                // Customers
                'view customers', 'create customers', 'edit customers', 'delete customers',
                'export customers',
                'view payment methods', 'manage payment methods', 'delete payment methods',

                // Catalog
                'view products', 'create products', 'edit products', 'delete products',
                'view prices', 'create prices', 'edit prices', 'delete prices',
                'view tax rates', 'manage tax rates',
                'view discounts', 'create discounts', 'edit discounts', 'delete discounts',

                // Payment links
                'view payment links', 'create payment links', 'edit payment links', 'delete payment links',
                'view checkout sessions',

                // Invoices
                'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
                'send invoices', 'void invoices',
                'view invoice reminders', 'manage invoice reminders',

                // Subscriptions
                'view subscriptions', 'create subscriptions', 'edit subscriptions', 'cancel subscriptions',

                // Payments
                'view payments', 'create payments', 'capture payments', 'void payments',
                'view payment attempts',

                // Refunds
                'view refunds', 'create refunds', 'approve refunds',

                // Payouts
                'view payouts', 'request payouts', 'cancel payouts', 'view settlements',

                // Balances & reports
                'view balance', 'view balance transactions',
                'view reports', 'export reports',

                'view notifications',
            ],

            'merchant_operator' => [
                'view own company',

                'view customers', 'create customers', 'edit customers',
                'view payment methods', 'manage payment methods',

                'view products', 'create products', 'edit products',
                'view prices', 'create prices', 'edit prices',
                'view discounts', 'create discounts', 'edit discounts',

                'view payment links', 'create payment links', 'edit payment links',
                'view checkout sessions',

                'view invoices', 'create invoices', 'edit invoices',
                'send invoices', 'void invoices',
                'view invoice reminders',

                'view subscriptions', 'create subscriptions', 'edit subscriptions', 'cancel subscriptions',

                'view payments', 'create payments', 'capture payments', 'void payments',
                'view payment attempts',

                'view refunds', 'create refunds',

                'view payouts', 'view settlements',
                'view balance', 'view balance transactions',
                'view reports',

                'view notifications',
            ],

            'merchant_viewer' => [
                'view own company',

                'view customers',
                'view products', 'view prices', 'view discounts', 'view tax rates',
                'view payment links', 'view checkout sessions',
                'view invoices',
                'view subscriptions',
                'view payments', 'view payment attempts',
                'view refunds',
                'view payouts', 'view settlements',
                'view balance', 'view balance transactions',
                'view reports',
                'view notifications',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if ($permissions === '*') {
                // super_admin: all permissions
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }

            $count = $permissions === '*' ? Permission::count() : count($permissions);
            $this->command->info("Role '{$roleName}' → {$count} permissions.");
        }

        $this->command->info('Roles seeded.');
    }
}
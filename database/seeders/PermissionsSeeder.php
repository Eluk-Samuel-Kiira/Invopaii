<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // ═══════════════════════════════════════════════════════════
            // PLATFORM: REFERENCE DATA — manage = create/edit/delete
            // ═══════════════════════════════════════════════════════════
            'view countries', 'manage countries',
            'view currencies', 'manage currencies',
            'view exchange rates', 'manage exchange rates',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: PAYMENT PROVIDERS
            // ═══════════════════════════════════════════════════════════
            'view providers', 'manage providers', 'create providers', 'edit providers', 'delete providers',
            'manage provider credentials',           // rotate secrets — super_admin only
            'view routing rules', 'manage routing rules',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: FEE SCHEDULES
            // ═══════════════════════════════════════════════════════════
            'view fee schedules', 'create fee schedules', 'edit fee schedules', 'delete fee schedules',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: COMPANIES (platform-side view/manage)
            // ═══════════════════════════════════════════════════════════
            'view companies', 'create companies', 'edit companies', 'delete companies',
            'approve companies', 'reject companies', 'suspend companies',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: COMPANY COMPLIANCE
            // ═══════════════════════════════════════════════════════════
            'view company documents', 'manage company documents',
            'run verification checks', 'view company compliance', 
            'view risk assessments', 'manage risk rules',
            'manage blocklist', 'manage velocity limits',
            'view company representatives',          
            'manage company representatives', 

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: DISPUTES
            // ═══════════════════════════════════════════════════════════
            'view disputes', 'manage disputes',
            'manage dispute evidence', 'submit dispute evidence',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: CROSS-MERCHANT OVERSIGHT
            // ═══════════════════════════════════════════════════════════
            'view all payments',
            'view all refunds', 'approve all refunds',
            'view all payouts', 'approve all payouts',
            'view all invoices',
            'view all customers',
            'view all subscriptions',
            'view ledger', 'manage ledger adjustments',
            'view reconciliation', 'manage reconciliation',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: USERS & ROLES
            // ═══════════════════════════════════════════════════════════
            'view users', 'create users', 'edit users', 'delete users',
            'impersonate users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
            'assign permissions',

            // ═══════════════════════════════════════════════════════════
            // PLATFORM: SYSTEM
            // ═══════════════════════════════════════════════════════════
            'view audit logs',
            'view api request logs',
            'view webhook events',
            'manage announcements',
            'view exports', 'create exports', 'delete exports',
            'manage system settings',
            'manage email templates',
            'view system health',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: COMPANY (own company, not platform oversight)
            // ═══════════════════════════════════════════════════════════
            'view own company', 'edit own company',
            'view company team', 'manage company team',
            'invite team members', 'remove team members',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: ONBOARDING / BANKING
            // ═══════════════════════════════════════════════════════════
            'submit company verification',
            'view company bank accounts', 'manage company bank accounts',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: API & WEBHOOKS
            // ═══════════════════════════════════════════════════════════
            'view api keys', 'create api keys', 'revoke api keys',
            'view webhooks', 'create webhooks', 'edit webhooks', 'delete webhooks',
            'view webhook deliveries', 'replay webhook deliveries',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: CUSTOMERS
            // ═══════════════════════════════════════════════════════════
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'export customers',
            'view payment methods', 'manage payment methods', 'delete payment methods',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: CATALOG
            // ═══════════════════════════════════════════════════════════
            'view products', 'create products', 'edit products', 'delete products',
            'view prices', 'create prices', 'edit prices', 'delete prices',
            'view tax rates', 'manage tax rates',
            'view discounts', 'create discounts', 'edit discounts', 'delete discounts',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: PAYMENT LINKS & CHECKOUT
            // ═══════════════════════════════════════════════════════════
            'view payment links', 'create payment links', 'edit payment links', 'delete payment links',
            'view checkout sessions',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: INVOICES
            // ═══════════════════════════════════════════════════════════
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
            'send invoices', 'void invoices',
            'view invoice reminders', 'manage invoice reminders',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: SUBSCRIPTIONS
            // ═══════════════════════════════════════════════════════════
            'view subscriptions', 'create subscriptions', 'edit subscriptions', 'cancel subscriptions',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: PAYMENTS
            // ═══════════════════════════════════════════════════════════
            'view payments', 'create payments', 'edit payments', 'capture payments', 'void payments',
            'view payment attempts',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: REFUNDS
            // ═══════════════════════════════════════════════════════════
            'view refunds', 'create refunds', 'approve refunds',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: PAYOUTS
            // ═══════════════════════════════════════════════════════════
            'view payouts', 'request payouts', 'cancel payouts',
            'view settlements',

            // ═══════════════════════════════════════════════════════════
            // MERCHANT: BALANCES & REPORTS
            // ═══════════════════════════════════════════════════════════
            'view balance', 'view balance transactions',
            'view reports', 'export reports',

            // ═══════════════════════════════════════════════════════════
            // SUPPORT
            // ═══════════════════════════════════════════════════════════
            'view notifications', 'manage notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Permissions seeded: ' . count($permissions) . ' total.');
    }
}
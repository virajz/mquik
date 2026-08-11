<?php

namespace App\Console\Commands;

use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Define the workshop's job roles and what each one may see.
 *
 * Everyone shares one Super Admin login today, which means every screen is on
 * every sidebar and nobody can tell which parts of the system are theirs. Roles
 * are built from module groups — the same grouping the sidebar uses — so a role
 * gains a whole area at a time, with named exceptions where a role needs a
 * single module out of someone else's area.
 */
class SetupRolesCommand extends Command
{
    protected $signature = 'mquik:setup-roles {--fresh : Replace each role\'s permissions instead of adding to them}';

    protected $description = 'Create the Service Advisor / Store Manager / Technician roles and grant their permissions.';

    /**
     * group access + explicit extras per role.
     *
     * @return array<string, array{groups: list<string>, modules: list<string>, except: list<string>, view_only: list<string>}>
     */
    protected function definitions(): array
    {
        return [
            'Service Advisor' => [
                // Owns the customer-facing side of a visit.
                'groups' => ['Workshop', 'Inspection', 'Customers', 'Vehicles', 'CRM', 'Locations'],
                'modules' => [
                    // Needs to raise the parts request and see the answer, but not
                    // run procurement.
                    'InternalPartsInquiry', 'VendorPurchaseInquiry',
                    'SalesEstimate', 'SalesEstimateApproval', 'Proforma',
                    'RegularSalesInvoice', 'DocumentCollection', 'DocumentDelivery',
                    'NotificationCenter', 'JobHistory',
                ],
                'except' => ['StockCounting', 'StockMismatchApproval', 'ExcessStockApproval'],
                // Reads the RFQ for price and part grade only — no vendor
                // identity, no ability to change the procurement side.
                'view_only' => ['VendorPurchaseInquiry'],
            ],

            'Store Manager' => [
                // Owns stock and everything that buys it.
                'groups' => ['Inventory', 'Purchase', 'Vendors'],
                'modules' => [
                    'InternalPartsInquiry', 'InternalPartOrder',
                    'GoodsReceipt', 'GoodsHandover', 'GoodsReturn', 'GoodsReturnNote',
                    'NotificationCenter', 'JobCard', 'SpareMaster', 'InventorySearch',
                ],
                'except' => [],
                'view_only' => [],
            ],

            'Technician' => [
                // Only the work in front of them.
                'groups' => [],
                'modules' => [
                    'TechnicianBench', 'VehicleInspectionOrder', 'DigitalInspection',
                    'FinalInspection', 'TechnicianFinding', 'JobCard', 'NotificationCenter',
                ],
                'except' => [],
                'view_only' => [],
            ],
        ];
    }

    public function handle(ModuleRegistry $registry): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = collect($registry->all());

        foreach ($this->definitions() as $roleName => $spec) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $allowed = [];

            foreach ($modules as $moduleName => $manifest) {
                if (in_array($moduleName, $spec['except'], true)) {
                    continue;
                }

                $inGroup = in_array($manifest['group'] ?? '', $spec['groups'], true);
                $named = in_array($moduleName, $spec['modules'], true);

                if (! $inGroup && ! $named) {
                    continue;
                }

                $viewOnly = in_array($moduleName, $spec['view_only'] ?? [], true);

                foreach ((array) ($manifest['permissions'] ?? []) as $slug) {
                    if ($viewOnly && ! Str::endsWith($slug, '.view')) {
                        continue;
                    }

                    $allowed[] = $slug;
                }
            }

            // A role that can open a screen also needs the masters behind its
            // pickers, otherwise every dropdown comes back empty.
            $allowed = array_merge($allowed, $this->lookupPermissions($modules));
            $allowed = array_values(array_unique($allowed));

            // Only grant permissions that actually exist (auth:sync-permissions
            // is the source of truth for that).
            $existing = Permission::whereIn('name', $allowed)->pluck('name')->all();

            $this->option('fresh')
                ? $role->syncPermissions($existing)
                : $role->givePermissionTo(array_diff($existing, $role->permissions->pluck('name')->all()));

            $this->line(str_pad($roleName, 18).count($existing).' permissions');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Roles ready. Assign with: php artisan mquik:make-user');

        return self::SUCCESS;
    }

    /**
     * Read-only access to every master, so pickers resolve for all roles.
     *
     * @param  Collection<string, array<string, mixed>>  $modules
     * @return list<string>
     */
    protected function lookupPermissions(Collection $modules): array
    {
        $out = [];

        foreach ($modules as $moduleName => $manifest) {
            if (! Str::endsWith($moduleName, 'Master')) {
                continue;
            }

            foreach ((array) ($manifest['permissions'] ?? []) as $slug) {
                if (Str::endsWith($slug, '.view')) {
                    $out[] = $slug;
                }
            }
        }

        return $out;
    }
}

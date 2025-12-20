<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Landlord\Tenant;
use Illuminate\Support\Facades\DB;

class MigrateAllTenants extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:migrate {--fresh : Drop all tables and re-run all migrations} {--seed : Seed the database after migrating}';

    /**
     * The console command description.
     */
    protected $description = 'Run migrations for all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fresh = $this->option('fresh');
        $seed = $this->option('seed');

        // Get all active tenants from landlord database
        $tenants = Tenant::on('mysql')
            ->where('status', 'active')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No active tenants found!');
            return 1;
        }

        $this->info("Found {$tenants->count()} active tenant(s)");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name} ({$tenant->subdomain})");
            
            try {
                // Make tenant current
                $tenant->makeCurrent();

                // Run migrations
                if ($fresh) {
                    $this->call('migrate:fresh', [
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                } else {
                    $this->call('migrate', [
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                }

                // Run seeder if requested
                if ($seed) {
                    $this->call('db:seed', [
                        '--force' => true,
                    ]);
                }

                $this->info("✅ Migration completed for: {$tenant->name}");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("❌ Migration failed for: {$tenant->name}");
                $this->error("Error: " . $e->getMessage());
                $failCount++;
            }

            $this->newLine();
        }

        // Forget current tenant
        Tenant::forgetCurrent();

        // Summary
        $this->newLine();
        $this->info("=================================");
        $this->info("Migration Summary:");
        $this->info("Total tenants: {$tenants->count()}");
        $this->info("✅ Successful: {$successCount}");
        if ($failCount > 0) {
            $this->error("❌ Failed: {$failCount}");
        }
        $this->info("=================================");

        return $failCount > 0 ? 1 : 0;
    }
}

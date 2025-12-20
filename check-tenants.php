<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenants = \App\Models\Landlord\Tenant::all(['id', 'subdomain', 'status', 'database', 'email']);

echo "Found " . $tenants->count() . " tenant(s):\n\n";

foreach ($tenants as $tenant) {
    echo "ID: {$tenant->id}\n";
    echo "Subdomain: {$tenant->subdomain}\n";
    echo "Status: {$tenant->status}\n";
    echo "Database: {$tenant->database}\n";
    echo "Email: {$tenant->email}\n";
    echo "---\n";
}


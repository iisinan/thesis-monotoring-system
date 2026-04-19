<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Route coordinator.supervisors.create: " . route('coordinator.supervisors.create') . "\n";
echo "Route admin.users.create: " . route('admin.users.create') . "\n";

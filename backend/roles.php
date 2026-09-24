<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Spatie\Permission\Models\Role;
print_r(Role::pluck('name')->toArray());

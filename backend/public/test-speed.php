<?php
$start = microtime(true);
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$time = microtime(true) - $start;
file_put_contents('php://stderr', "Request handled in {$time}s\n");
$response->send();
$kernel->terminate($request, $response);

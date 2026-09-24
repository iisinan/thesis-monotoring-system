<?php
$data = array_map('str_getcsv', file('storage/app/supervisors.csv'));
$supervisors = [];

foreach ($data as $row) {
    $name = trim($row[1] ?? '');
    $email = trim($row[2] ?? '');
    $phone = trim($row[3] ?? '');
    
    if (empty($name) || empty($email) || strpos($email, '@') === false || strtolower($email) === 'e-mail') {
        continue;
    }
    
    // Clean name: remove trailing spaces, standardize
    $supervisors[] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ];
}

file_put_contents('storage/app/supervisors.json', json_encode($supervisors, JSON_PRETTY_PRINT));
echo "Found " . count($supervisors) . " supervisors.\n";

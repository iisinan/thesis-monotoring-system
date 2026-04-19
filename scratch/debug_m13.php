<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StudentMilestone;

$milestones = StudentMilestone::whereHas('template', function($q) {
    $q->where('order', 13);
})->get();

foreach ($milestones as $m) {
    echo "ID: " . $m->id . "\n";
    echo "Thesis: " . $m->thesis_project_id . "\n";
    echo "Unlocked: " . ($m->is_submission_unlocked ? 'YES' : 'NO') . "\n";
    echo "Template Submission Req Approval: " . ($m->template->submission_requires_approval ? 'YES' : 'NO') . "\n";
    echo "Approver Roles: " . json_encode($m->template->submission_approver_roles) . "\n";
    echo "--- \n";
}

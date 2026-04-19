<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use Illuminate\Http\Request;

class MilestoneTemplateController extends Controller
{
    public function index()
    {
        $templates = MilestoneTemplate::with('program')->orderBy('order')->get();
        return view('admin.milestone-templates.index', compact('templates'));
    }

    public function create()
    {
        $programs = Program::all();
        $roles = ['Admin', 'Supervisor', 'Program Coordinator', 'Internal Examiner', 'External Examiner'];
        return view('admin.milestone-templates.create', compact('programs', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'program_id' => 'nullable|exists:programs,id',
            'requires_submission' => 'boolean',
            'submission_requires_approval' => 'boolean',
            'submission_approver_roles' => 'nullable|array',
            'requires_approval' => 'boolean',
            'has_chat' => 'boolean',
            'show_supervisor_details' => 'boolean',
            'required_approvers' => 'nullable|array',
            'approval_threshold' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'submission_type' => 'required|array|min:1',
            'submission_type.*' => 'string|in:file,publication',
            'allow_defence_date' => 'boolean',
            'defence_type' => 'nullable|string|in:proposal,internal,external',
            'defence_date_role' => 'nullable|string',
            'is_final_archival' => 'boolean',
            'show_supervisor_assignment' => 'boolean',
            'show_internal_examiner_assignment' => 'boolean',
            'show_external_examiner_assignment' => 'boolean',
            'allow_plagiarism_report' => 'boolean',
            'plagiarism_report_role' => 'nullable|string|in:Admin,Program Coordinator',
        ]);

        // Fix for checkboxes
        $validated['requires_submission'] = $request->has('requires_submission');
        $validated['submission_requires_approval'] = $request->has('submission_requires_approval');
        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['has_chat'] = $request->has('has_chat');
        $validated['show_supervisor_details'] = $request->has('show_supervisor_details');
        $validated['allow_defence_date'] = $request->has('allow_defence_date');
        $validated['is_final_archival'] = $request->has('is_final_archival');
        $validated['show_supervisor_assignment'] = $request->has('show_supervisor_assignment');
        $validated['show_internal_examiner_assignment'] = $request->has('show_internal_examiner_assignment');
        $validated['show_external_examiner_assignment'] = $request->has('show_external_examiner_assignment');
        $validated['allow_plagiarism_report'] = $request->has('allow_plagiarism_report');

        // Order is auto-handled by model boot events (shift or append)
        $template = MilestoneTemplate::create($validated);

        // Sync this new milestone with existing thesis projects
        $projects = \App\Models\ThesisProject::query();
        if ($template->program_id) {
            $projects->whereHas('student', fn($q) => $q->where('program_id', $template->program_id));
        }
        
        $syncCount = 0;
        foreach ($projects->get() as $project) {
            $project->syncMilestones();
            $syncCount++;
        }

        // Create a system announcement to notify users
        \App\Models\Announcement::create([
            'title' => 'New Milestone Requirement: ' . $template->name,
            'content' => 'A new institutional milestone has been added to the graduation track. It has been automatically synchronized with ' . $syncCount . ' active research projects.',
            'type' => 'info',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'target_role' => null, // null means all roles
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.milestone-templates.index')->with('success', 'Milestone template created and synchronized with ' . $syncCount . ' projects.');
    }

    public function edit(MilestoneTemplate $milestoneTemplate)
    {
        $programs = Program::all();
        $roles = ['Admin', 'Supervisor', 'Program Coordinator', 'Internal Examiner', 'External Examiner'];
        return view('admin.milestone-templates.edit', compact('milestoneTemplate', 'programs', 'roles'));
    }

    public function update(Request $request, MilestoneTemplate $milestoneTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer',
            'program_id' => 'nullable|exists:programs,id',
            'requires_submission' => 'boolean',
            'submission_requires_approval' => 'boolean',
            'submission_approver_roles' => 'nullable|array',
            'requires_approval' => 'boolean',
            'has_chat' => 'boolean',
            'show_supervisor_details' => 'boolean',
            'required_approvers' => 'nullable|array',
            'approval_threshold' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'submission_type' => 'required|array|min:1',
            'submission_type.*' => 'string|in:file,publication',
            'allow_defence_date' => 'boolean',
            'defence_type' => 'nullable|string|in:proposal,internal,external',
            'defence_date_role' => 'nullable|string',
            'is_final_archival' => 'boolean',
            'show_supervisor_assignment' => 'boolean',
            'show_internal_examiner_assignment' => 'boolean',
            'show_external_examiner_assignment' => 'boolean',
            'allow_plagiarism_report' => 'boolean',
            'plagiarism_report_role' => 'nullable|string|in:Admin,Program Coordinator',
        ]);

        // Fix for checkboxes
        $validated['requires_submission'] = $request->has('requires_submission');
        $validated['submission_requires_approval'] = $request->has('submission_requires_approval');
        $validated['requires_approval'] = $request->has('requires_approval');
        $validated['has_chat'] = $request->has('has_chat');
        $validated['show_supervisor_details'] = $request->has('show_supervisor_details');
        $validated['allow_defence_date'] = $request->has('allow_defence_date');
        $validated['is_final_archival'] = $request->has('is_final_archival');
        $validated['show_supervisor_assignment'] = $request->has('show_supervisor_assignment');
        $validated['show_internal_examiner_assignment'] = $request->has('show_internal_examiner_assignment');
        $validated['show_external_examiner_assignment'] = $request->has('show_external_examiner_assignment');
        $validated['allow_plagiarism_report'] = $request->has('allow_plagiarism_report');

        $milestoneTemplate->update($validated);

        // Clean up any gaps
        MilestoneTemplate::renumberSequence($milestoneTemplate->program_id);

        // Sync with existing thesis projects (in case it's newly relevant)
        $projects = \App\Models\ThesisProject::query();
        if ($milestoneTemplate->program_id) {
            $projects->whereHas('student', fn($q) => $q->where('program_id', $milestoneTemplate->program_id));
        }
        
        foreach ($projects->get() as $project) {
            $project->syncMilestones();
        }

        return redirect()->route('admin.milestone-templates.index')->with('success', 'Milestone template updated and synchronized.');
    }

    public function destroy(MilestoneTemplate $milestoneTemplate)
    {
        $milestoneTemplate->delete();
        return redirect()->route('admin.milestone-templates.index')->with('success', 'Milestone template deleted successfully.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:milestone_templates,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            $template = MilestoneTemplate::find($id);
            if ($template) {
                // We update without triggering the model events to avoid infinite reordering
                $template->order = $index + 1;
                $template->saveQuietly();
            }
        }

        return response()->json(['success' => true]);
    }
}

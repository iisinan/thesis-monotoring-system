<?php

namespace App\Http\Controllers\Examiner;

use App\Http\Controllers\Controller;
use App\Models\ExaminerAssignment;
use App\Models\ExaminerReview;
use App\Models\InboxAttachment;
use App\Models\InboxMessage;
use App\Models\StudentMilestone;
use App\Models\Submission;
use App\Models\ThesisProject;
use App\Models\User;
use App\Services\MilestoneWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function __construct(protected MilestoneWorkflowService $workflowService)
    {
    }

    /**
     * Save a new examiner review (either private for own consumption or sent to the student).
     */
    public function store(Request $request, ThesisProject $thesis)
    {
        $user = Auth::user();
        $this->authorizeThesisExaminer($thesis, $user);

        $validated = $request->validate([
            'submission_id' => 'nullable|uuid|exists:submissions,id',
            'student_milestone_id' => 'nullable|uuid|exists:student_milestones,id',
            'stage' => 'nullable|in:pre_defence,post_defence,general',
            'recommendation' => 'nullable|string|max:60',
            'content' => 'required|string|max:10000',
            'action' => 'required|in:save_private,send_to_student',
            'send_email' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,txt,png,jpg,jpeg|max:20480',
        ]);

        $milestoneId = $validated['student_milestone_id'] ?? null;
        $submissionId = $validated['submission_id'] ?? null;

        if ($submissionId) {
            $submission = Submission::with('milestone')->findOrFail($submissionId);
            if ((string) $submission->milestone?->thesis_project_id !== (string) $thesis->id) {
                abort(403, 'Submission does not belong to this thesis.');
            }
            $milestoneId = $milestoneId ?: $submission->student_milestone_id;
        }

        if ($milestoneId) {
            $milestone = StudentMilestone::findOrFail($milestoneId);
            if ((string) $milestone->thesis_project_id !== (string) $thesis->id) {
                abort(403, 'Milestone does not belong to this thesis.');
            }
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('examiner_reviews/' . $thesis->id, 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $isShared = $validated['action'] === 'send_to_student';
        $sendEmail = $request->has('send_email') ? $request->boolean('send_email') : true;

        $review = ExaminerReview::create([
            'thesis_project_id' => $thesis->id,
            'student_milestone_id' => $milestoneId,
            'submission_id' => $submissionId,
            'examiner_id' => $user->id,
            'stage' => $validated['stage'] ?? 'pre_defence',
            'recommendation' => $validated['recommendation'] ?? 'personal_note',
            'content' => trim($validated['content']),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_shared_with_student' => $isShared,
            'shared_at' => $isShared ? now() : null,
            'sent_via_email' => false,
        ]);

        if ($isShared) {
            $emailed = $this->deliverReviewToStudent($review, $thesis, $user, $sendEmail);
            $studentName = $thesis->student?->user?->name ?? 'the candidate';
            $suffix = $emailed ? ' and delivered to their email & portal inbox' : ' and posted to their portal';
            return back()->with('success', "Examiner review saved{$suffix} for {$studentName}.");
        }

        return back()->with('success', 'Private review note saved for your personal consumption.');
    }

    /**
     * Update an existing examiner review (and optionally send it to the student).
     */
    public function update(Request $request, ExaminerReview $review)
    {
        $user = Auth::user();
        if ((string) $review->examiner_id !== (string) $user->id && !$user->hasRole('Admin')) {
            abort(403, 'You can only edit your own examiner reviews.');
        }

        $validated = $request->validate([
            'recommendation' => 'nullable|string|max:60',
            'content' => 'required|string|max:10000',
            'action' => 'nullable|in:save_private,send_to_student',
            'send_email' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,txt,png,jpg,jpeg|max:20480',
        ]);

        $action = $validated['action'] ?? ($review->is_shared_with_student ? 'send_to_student' : 'save_private');
        $shouldShare = $action === 'send_to_student';
        $sendEmail = $request->has('send_email') ? $request->boolean('send_email') : true;

        $attachmentPath = $review->attachment_path;
        $attachmentName = $review->attachment_name;
        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                try {
                    Storage::disk('public')->delete($attachmentPath);
                } catch (\Throwable $e) {
                }
            }
            $file = $request->file('attachment');
            $attachmentPath = $file->store('examiner_reviews/' . $review->thesis_project_id, 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $review->update([
            'recommendation' => $validated['recommendation'] ?? $review->recommendation,
            'content' => trim($validated['content']),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_shared_with_student' => $shouldShare,
            'shared_at' => $shouldShare ? ($review->shared_at ?? now()) : null,
        ]);

        $thesis = $review->thesis;
        if ($shouldShare && $thesis) {
            $emailed = $this->deliverReviewToStudent($review, $thesis, $user, $sendEmail);
            $studentName = $thesis->student?->user?->name ?? 'the candidate';
            $suffix = $emailed ? ' and sent via email & portal inbox' : ' and shared to portal';
            return back()->with('success', "Review updated{$suffix} for {$studentName}.");
        }

        return back()->with('success', 'Private review note updated.');
    }

    /**
     * Share an already-saved private review with the student.
     */
    public function share(Request $request, ExaminerReview $review)
    {
        $user = Auth::user();
        if ((string) $review->examiner_id !== (string) $user->id && !$user->hasRole('Admin')) {
            abort(403, 'You can only share your own examiner reviews.');
        }

        $sendEmail = $request->has('send_email') ? $request->boolean('send_email') : true;

        $review->update([
            'is_shared_with_student' => true,
            'shared_at' => now(),
        ]);

        $thesis = $review->thesis;
        if ($thesis) {
            $emailed = $this->deliverReviewToStudent($review, $thesis, $user, $sendEmail);
            $studentName = $thesis->student?->user?->name ?? 'the candidate';
            $suffix = $emailed ? ' via email & portal inbox' : ' to their portal';
            return back()->with('success', "Review sent{$suffix} for {$studentName}.");
        }

        return back()->with('success', 'Review shared with student.');
    }

    /**
     * View or download a review attachment (strictly respects private vs shared status).
     */
    public function downloadAttachment(Request $request, ExaminerReview $review)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $isOwner = (string) $review->examiner_id === (string) $user->id;
        $isAdmin = $user->hasAnyRole(['Admin', 'Director']);
        $isAssignedStudent = $review->is_shared_with_student
            && (string) $review->thesis?->student?->user_id === (string) $user->id;

        if (!$isOwner && !$isAdmin && !$isAssignedStudent) {
            abort(403, 'Unauthorized to access this review attachment.');
        }

        if (!$review->attachment_path || !Storage::disk('public')->exists($review->attachment_path)) {
            abort(404, 'Review attachment not found.');
        }

        $filename = $review->attachment_name ?: basename($review->attachment_path);
        $mimeType = Storage::disk('public')->mimeType($review->attachment_path) ?: 'application/octet-stream';

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($review->attachment_path, $filename);
        }

        return Storage::disk('public')->response($review->attachment_path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '\"', $filename) . '"',
        ]);
    }

    /**
     * Delete a saved examiner review note.
     */
    public function destroy(ExaminerReview $review)
    {
        $user = Auth::user();
        if ((string) $review->examiner_id !== (string) $user->id && !$user->hasRole('Admin')) {
            abort(403, 'You can only delete your own examiner reviews.');
        }

        $studentUserId = $review->thesis?->student?->user_id;

        if ($review->attachment_path) {
            try {
                Storage::disk('public')->delete($review->attachment_path);
            } catch (\Throwable $e) {
            }
        }

        $review->delete();

        if ($studentUserId) {
            Cache::forget('user_thesis_' . $studentUserId);
        }

        return back()->with('success', 'Examiner review note removed.');
    }

    /**
     * Send a direct message (Portal Inbox and/or Email) from the Internal Examiner to the student.
     */
    public function messageStudent(Request $request, ThesisProject $thesis)
    {
        $user = Auth::user();
        $this->authorizeThesisExaminer($thesis, $user);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'delivery_method' => 'nullable|in:both,in_app,email',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,txt,png,jpg,jpeg|max:20480',
        ]);

        $thesis->loadMissing('student.user');
        $studentUser = $thesis->student?->user;
        if (!$studentUser) {
            return back()->with('error', 'Could not locate the candidate\'s user account or email address.');
        }

        $deliveryMethod = $validated['delivery_method'] ?? 'both';

        $inboxMessage = InboxMessage::create([
            'sender_id' => $user->id,
            'subject' => trim($validated['subject']),
            'body' => trim($validated['body']),
            'delivery_method' => $deliveryMethod,
        ]);

        $inboxMessage->recipients()->attach($studentUser->id, [
            'id' => (string) Str::uuid(),
            'recipient_type' => 'to',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('inbox_attachments', 'public');
            InboxAttachment::create([
                'inbox_message_id' => $inboxMessage->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        if (in_array($deliveryMethod, ['in_app', 'both'], true)) {
            try {
                \App\Events\MessageReceived::dispatch($inboxMessage, $studentUser->id);
            } catch (\Throwable $e) {
                Log::warning('Examiner message broadcast failed: ' . $e->getMessage());
            }
        }

        $emailSent = false;
        if (in_array($deliveryMethod, ['email', 'both'], true) && !empty($studentUser->email)) {
            try {
                Notification::sendNow($studentUser, new \App\Notifications\NewInboxMessage($inboxMessage));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::error('Examiner direct email failed: ' . $e->getMessage());
            }
        }

        $targetDesc = $emailSent
            ? "{$studentUser->name} ({$studentUser->email})"
            : "{$studentUser->name}'s portal inbox";

        return back()->with('success', "Message sent to {$targetDesc}.");
    }

    private function deliverReviewToStudent(ExaminerReview $review, ThesisProject $thesis, User $examiner, bool $sendEmail): bool
    {
        $thesis->loadMissing('student.user');
        $studentUser = $thesis->student?->user;
        if (!$studentUser) {
            return false;
        }

        Cache::forget('user_thesis_' . $studentUser->id);

        $docLabel = $review->submission?->file_meta['original_name']
            ?? ($review->milestone?->template?->name ?? 'Thesis Manuscript');

        $subject = "Internal Examiner Review: " . Str::limit($thesis->title ?? 'Thesis Manuscript', 90);
        $body = "Dear {$studentUser->name},\n\n"
            . "{$examiner->name} (Internal Examiner) has shared an assessment note regarding \"{$docLabel}\".\n"
            . "Assessment Category: {$review->recommendation_label}\n\n"
            . "Examiner Review:\n"
            . "{$review->content}\n\n"
            . ($review->attachment_name ? "Attached File: {$review->attachment_name} (viewable in your Portal Inbox & Thesis Overview)\n\n" : '')
            . "You can also view this feedback inside your Postgraduate Portal.";

        $deliveryMethod = $sendEmail ? 'both' : 'in_app';

        $inboxMessage = InboxMessage::create([
            'sender_id' => $examiner->id,
            'subject' => $subject,
            'body' => $body,
            'delivery_method' => $deliveryMethod,
        ]);

        $inboxMessage->recipients()->attach($studentUser->id, [
            'id' => (string) Str::uuid(),
            'recipient_type' => 'to',
        ]);

        if ($review->attachment_path && Storage::disk('public')->exists($review->attachment_path)) {
            $baseName = $review->attachment_name ?: basename($review->attachment_path);
            $inboxCopyPath = 'inbox_attachments/' . Str::uuid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName);
            Storage::disk('public')->copy($review->attachment_path, $inboxCopyPath);
            $inboxMessage->attachments()->create([
                'file_path' => $inboxCopyPath,
                'file_name' => $baseName,
                'file_size' => Storage::disk('public')->size($inboxCopyPath),
                'mime_type' => Storage::disk('public')->mimeType($inboxCopyPath),
            ]);
        }

        try {
            \App\Events\MessageReceived::dispatch($inboxMessage, $studentUser->id);
        } catch (\Throwable $e) {
            Log::warning('Examiner review broadcast error: ' . $e->getMessage());
        }

        if ($sendEmail && !empty($studentUser->email)) {
            try {
                Notification::sendNow($studentUser, new \App\Notifications\NewInboxMessage($inboxMessage));
                $review->update(['sent_via_email' => true]);
                return true;
            } catch (\Throwable $e) {
                Log::error('Examiner review email delivery failed: ' . $e->getMessage());
            }
        }

        return false;
    }

    private function authorizeThesisExaminer(ThesisProject $thesis, ?User $user): void
    {
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->hasAnyRole(['Admin', 'Director'])) {
            return;
        }

        if ($this->workflowService->isAssignedInternalExaminerForThesis($thesis, $user)) {
            return;
        }

        if ($thesis->external_examiner_profile_id && $user->externalExaminerProfiles()->where('id', $thesis->external_examiner_profile_id)->exists()) {
            return;
        }

        if (ExaminerAssignment::where('thesis_project_id', $thesis->id)->where('examiner_id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Unauthorized: You are not an assigned examiner for this thesis.');
    }
}

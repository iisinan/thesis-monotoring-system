@extends('layouts.dashboard')

@section('header', 'Examination Dossier')

@section('content')
@php
    $student = $thesis->student ?? $thesis->studentProfile;
    $studentUser = $student?->user;
    $roleLabel = 'Examiner';
    if (in_array($thesis->internal_examiner_profile_id, $internalProfileIds)) {
        $roleLabel = 'Internal Examiner';
    } elseif (in_array($thesis->external_examiner_profile_id, $externalProfileIds)) {
        $roleLabel = 'External Examiner';
    }

    $workflow = app(\App\Services\MilestoneWorkflowService::class);
    $milestones = ($thesis->milestones ?? collect())->sortBy(fn($m) => $m->template?->order ?? 999)->values();
    $internalDefenceMilestone = $milestones->first(fn($m) => $workflow->isInternalDefence($m));
    $isPostDefenceStage = $internalDefenceMilestone && $workflow->isPostDefencePhase($internalDefenceMilestone);
    $postDefenceSub = $isPostDefenceStage ? $workflow->getPostDefenceSubmission($internalDefenceMilestone) : null;
    $canApproveCorrections = $internalDefenceMilestone && ($workflow->isAssignedInternalExaminer($internalDefenceMilestone, auth()->user()) || auth()->user()->hasRole('Admin'));
    $targetMilestoneId = $internalDefenceMilestone?->id ?? $thesis->currentMilestone?->id;

    $allSubs = $milestones->flatMap(function ($m) {
        return ($m->submissions ?? collect())->map(function ($sub) use ($m) {
            $sub->setRelation('milestone', $m);
            return $sub;
        });
    })->sortByDesc('created_at')->values();

    $docsForJs = $allSubs->map(function ($s) {
        $mName = $s->milestone?->template?->name ?? 'Milestone';
        $fName = $s->file_meta['original_name'] ?? ('Document v' . $s->version);
        return [
            'id' => (string) $s->id,
            'label' => "{$mName} — {$fName} (v{$s->version})",
        ];
    })->values();

    $myReviews = ($thesis->examinerReviews ?? collect())->sortByDesc('created_at')->values();

    $detectPreviewType = function ($sub) {
        if (!$sub) return 'pdf';
        $name = strtolower(($sub->file_meta['original_name'] ?? '') . ' ' . ($sub->file_url ?? ''));
        $mime = strtolower($sub->file_meta['mime_type'] ?? '');
        if (str_contains($mime, 'image') || preg_match('/\.(jpg|jpeg|png|gif|webp)(\?|$)/i', $name)) {
            return 'image';
        }
        if (preg_match('/\.(doc|docx|xls|xlsx|ppt|pptx)(\?|$)/i', $name) && !str_contains($mime, 'pdf')) {
            return 'other';
        }
        return 'pdf';
    };
@endphp

<div x-data="{
    reviewModalOpen: false,
    reviewMode: 'create',
    reviewFormAction: '{{ route('examiner.reviews.store', $thesis) }}',
    reviewSubmissionId: '{{ $allSubs->first()?->id ?? '' }}',
    reviewRecommendation: 'personal_note',
    reviewContent: '',
    reviewSendEmail: true,
    messageModalOpen: false,

    openEditReview(actionUrl, submissionId, recommendation, content) {
        this.reviewMode = 'edit';
        this.reviewFormAction = actionUrl;
        this.reviewSubmissionId = submissionId || '';
        this.reviewRecommendation = recommendation || 'personal_note';
        this.reviewContent = content || '';
        this.reviewSendEmail = true;
        this.reviewModalOpen = true;
    },
    openNewReview() {
        this.reviewMode = 'create';
        this.reviewFormAction = '{{ route('examiner.reviews.store', $thesis) }}';
        this.reviewSubmissionId = '{{ ($postDefenceSub ?? $allSubs->first())?->id ?? '' }}';
        this.reviewRecommendation = 'personal_note';
        this.reviewContent = '';
        this.reviewSendEmail = true;
        this.reviewModalOpen = true;
    }
}"
@keydown.escape.window="reviewModalOpen = false; messageModalOpen = false"
class="space-y-6">

    {{-- Header --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div class="space-y-2 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 text-[10px] font-black uppercase tracking-wider">{{ $roleLabel }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-black uppercase tracking-wider">{{ $student?->student_id_number ?? 'N/A' }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-bold uppercase">{{ $student?->program?->name ?? 'Program' }}</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-snug">{{ $thesis->title ?? 'Untitled Thesis' }}</h2>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">
                Candidate: <strong class="text-slate-800">{{ $studentUser?->name ?? 'N/A' }}</strong>
                @if($studentUser?->email)
                    · <span class="text-slate-600">{{ $studentUser->email }}</span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            @if($studentUser)
                <button type="button" @click="messageModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Message &amp; Email Student
                </button>
            @endif
            <button type="button" @click="openNewReview()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-sm cursor-pointer">
                + Write Review
            </button>
            <a href="{{ route('milestones.index', ['thesis_id' => $thesis->id, 'expanded' => $targetMilestoneId]) }}"
               class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                Milestones Workspace
            </a>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                Dashboard
            </a>
        </div>
    </div>

    {{-- Post-Defence Correction Verification Banner --}}
    @if($isPostDefenceStage && $canApproveCorrections && $internalDefenceMilestone->status !== 'approved')
    <div class="p-6 rounded-3xl border-2 border-purple-200 bg-purple-50/70 space-y-4 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                    ⚖️
                </div>
                <div>
                    <h3 class="text-sm font-black text-purple-950 uppercase tracking-wider">Post-Defence Correction Verification (Internal Defence)</h3>
                    <p class="text-xs font-medium text-purple-800 mt-0.5">
                        Candidate passed the Internal Defence presentation. Review their corrected thesis manuscript below to advance them to Viva (Milestone 07).
                    </p>
                </div>
            </div>
            <a href="{{ route('milestones.show', $internalDefenceMilestone) }}" class="px-3 py-1.5 bg-white border border-purple-200 text-purple-700 hover:bg-purple-100 rounded-xl text-[10px] font-black uppercase tracking-wider shrink-0 transition-all">
                Open Milestone View
            </a>
        </div>

        @if($postDefenceSub)
            @php
                $postName = $postDefenceSub->file_meta['original_name'] ?? 'Corrected Thesis Document';
                $postType = $detectPreviewType($postDefenceSub);
            @endphp
            <div class="p-4 bg-white rounded-2xl border border-purple-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-[9px] font-black uppercase tracking-wider">Corrected Thesis Uploaded</span>
                    <p class="text-xs font-bold text-slate-900 mt-1">{{ $postName }} (v.0{{ $postDefenceSub->version }})</p>
                    @if($postDefenceSub->description)
                        <p class="text-[11px] text-slate-600 mt-0.5 italic">"{{ $postDefenceSub->description }}"</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                        @click.prevent="$dispatch('open-document-preview', {
                            url: '{{ route('submissions.view', $postDefenceSub->id) }}',
                            title: {{ \Illuminate\Support\Js::from($postName) }},
                            type: '{{ $postType }}'
                        })"
                        class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                        Open Thesis PDF
                    </button>
                    <a href="{{ route('submissions.view', [$postDefenceSub->id, 'download' => 1]) }}" class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                        Download
                    </a>
                </div>
            </div>

            <form action="{{ route('milestones.review.update', $internalDefenceMilestone) }}" method="POST" class="space-y-3 bg-white p-5 rounded-2xl border border-purple-100">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1.5">Internal Examiner Decision</label>
                    <select name="decision" required class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 py-2.5 px-3 focus:ring-purple-500 focus:border-purple-500">
                        <option value="approved">Approve Corrected Thesis &amp; Advance Candidate to Viva</option>
                        <option value="revision_required">Request Further Corrections</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1.5">Examiner Remarks / Verification Notes <span class="text-rose-500">*</span></label>
                    <textarea name="remarks" rows="3" required class="w-full rounded-xl border-slate-200 text-xs p-3 focus:ring-purple-500 focus:border-purple-500" placeholder="Provide your verification notes on the candidate's post-defence corrections..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md shadow-purple-600/20 transition-all cursor-pointer">
                        Submit Correction Decision
                    </button>
                </div>
            </form>
        @else
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs font-bold text-amber-900">
                Waiting for the candidate to upload their post-defence corrected thesis manuscript. Once uploaded, you will be able to review and approve it here.
            </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column (2 cols): Milestone Submissions + Examiner Reviews --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Submissions / Milestones --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-black text-slate-800 text-base uppercase tracking-tight">Uploaded Milestone Documents</h3>
                    <span class="text-xs font-bold text-slate-500">{{ $allSubs->count() }} {{ Str::plural('File', $allSubs->count()) }}</span>
                </div>
                <div class="p-6 space-y-6">
                    @forelse($milestones as $milestone)
                        <div class="pb-6 border-b border-slate-100 last:pb-0 last:border-b-0">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-black text-slate-800 text-sm">{{ $milestone->template->name }}</h4>
                                <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full {{ $milestone->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ str_replace('_', ' ', $milestone->status) }}
                                </span>
                            </div>

                            @if($milestone->submissions->isNotEmpty())
                                <ul class="space-y-2.5">
                                    @foreach($milestone->submissions as $submission)
                                        @php
                                            $subType = $detectPreviewType($submission);
                                            $subName = $submission->file_meta['original_name'] ?? ('Document v' . $submission->version . '.pdf');
                                        @endphp
                                        <li class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl border border-slate-200 bg-slate-50/70">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-purple-600 shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black text-slate-800 truncate">{{ $subName }} <span class="text-slate-400">(v.0{{ $submission->version }})</span></p>
                                                    <p class="text-[11px] text-slate-500">{{ $submission->created_at->format('M d, Y H:i') }} · {{ strtoupper($submission->type ?? 'file') }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                                                <button type="button"
                                                    @click.prevent="$dispatch('open-document-preview', {
                                                        url: '{{ route('submissions.view', $submission->id) }}',
                                                        title: {{ \Illuminate\Support\Js::from($subName) }},
                                                        type: '{{ $subType }}'
                                                    })"
                                                    class="px-3.5 py-2 text-[10px] font-black uppercase tracking-wider text-white bg-slate-900 rounded-xl hover:bg-black transition-colors cursor-pointer">
                                                    View Thesis
                                                </button>
                                                <a href="{{ route('submissions.view', [$submission->id, 'download' => 1]) }}"
                                                   class="px-3 py-2 text-[10px] font-black uppercase tracking-wider text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors">
                                                    Download
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-slate-400 italic p-3 bg-slate-50 rounded-xl border border-slate-100">No submissions uploaded for this milestone.</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 italic p-6 text-center">No milestones are currently defined for this thesis.</p>
                    @endforelse
                </div>
            </div>

            {{-- Examiner Reviews (Private vs Shared) --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-slate-800 text-base uppercase tracking-tight">Examiner Review Notes</h3>
                        <p class="text-xs text-slate-500">Save private notes for your own consumption or send them directly to the candidate.</p>
                    </div>
                    <button type="button" @click="openNewReview()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider cursor-pointer">
                        + New Review
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($myReviews as $rev)
                        <div class="p-4 rounded-2xl border {{ $rev->is_shared_with_student ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200' }} space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($rev->is_shared_with_student)
                                        <span class="px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">Sent to Student{{ $rev->sent_via_email ? ' · Emailed' : '' }}</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md bg-slate-900 text-white text-[10px] font-black uppercase">Private (Only You)</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 text-[10px] font-bold">{{ $rev->recommendation_label }}</span>
                                </div>
                                <span class="text-xs text-slate-400">{{ $rev->updated_at?->diffForHumans() }}</span>
                            </div>

                            <p class="text-xs text-slate-800 whitespace-pre-line leading-relaxed">{{ $rev->content }}</p>

                            @if($rev->attachment_path)
                                <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200/80 text-xs">
                                    <span class="font-bold text-slate-700 truncate">📎 {{ $rev->attachment_name ?: 'Review Attachment' }}</span>
                                    <a href="{{ route('examiner.reviews.attachment', [$rev, 'download' => 1]) }}" class="font-black text-purple-600 hover:text-purple-800 text-[10px] uppercase shrink-0">Download</a>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200/70">
                                <div class="flex items-center gap-3">
                                    <button type="button"
                                        @click="openEditReview(
                                            '{{ route('examiner.reviews.update', $rev) }}',
                                            '{{ $rev->submission_id ?? '' }}',
                                            '{{ $rev->recommendation ?? 'personal_note' }}',
                                            {{ \Illuminate\Support\Js::from($rev->content) }}
                                        )"
                                        class="text-[10px] font-black uppercase tracking-wider text-slate-600 hover:text-purple-700 cursor-pointer">
                                        Edit
                                    </button>
                                    <form action="{{ route('examiner.reviews.destroy', $rev) }}" method="POST" class="inline"
                                          data-confirm="Delete this saved review note?"
                                          data-confirm-title="Delete Review Note"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black uppercase tracking-wider text-rose-500 hover:text-rose-700 cursor-pointer">Delete</button>
                                    </form>
                                </div>

                                @if(!$rev->is_shared_with_student)
                                    <form action="{{ route('examiner.reviews.share', $rev) }}" method="POST" class="inline"
                                          data-confirm="Send this private review to {{ addslashes($studentUser?->name ?? 'the student') }} via portal and email?"
                                          data-confirm-title="Send Review to Student"
                                          data-confirm-type="success"
                                          data-confirm-btn="Send to Student">
                                        @csrf
                                        <input type="hidden" name="send_email" value="1">
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider cursor-pointer">
                                            Send to Student (+ Email)
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                            <p class="text-xs font-bold text-slate-500">No saved reviews yet.</p>
                            <p class="text-[11px] text-slate-400 mt-1">Click "+ New Review" above to keep private notes or send feedback to the candidate.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column: Assignment Info & Evaluations --}}
        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-base uppercase tracking-tight">Assignment Info</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase mb-1">Your Role</p>
                        <p class="font-bold text-slate-800">{{ $roleLabel }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase mb-1">Candidate Email</p>
                        <p class="font-semibold text-slate-700 text-sm">{{ $studentUser?->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-400 uppercase mb-1">Supervisors</p>
                        @php
                            $sups = ($thesis->assignments ?? collect())->where('status', 'active')->map(fn($a) => $a->supervisor?->user?->name)->filter()->implode(', ');
                        @endphp
                        <p class="font-semibold text-slate-800 text-sm">{{ $sups ?: 'None listed' }}</p>
                    </div>
                </div>
            </div>

            @php
                $defenceEvents = $thesis->defenceEvents()->orderBy('schedule_start', 'desc')->get();
            @endphp
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-base uppercase tracking-tight">Oral Defence Evaluation</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($defenceEvents as $event)
                        @php
                            $existingEvaluation = \App\Models\Evaluation::where('defence_event_id', $event->id)
                                ->where('evaluator_id', auth()->id())
                                ->first();
                        @endphp
                        <div class="p-4 rounded-2xl border {{ $existingEvaluation ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $event->type) }}</h4>
                                    <p class="text-xs text-slate-500">{{ $event->schedule_start ? $event->schedule_start->format('M d, Y H:i') : 'Scheduled' }}</p>
                                </div>
                                @if($existingEvaluation)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-black uppercase bg-green-100 text-green-700">Submitted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700">Pending</span>
                                @endif
                            </div>

                            @if($existingEvaluation)
                                <a href="{{ route('evaluations.show', $existingEvaluation->id) }}" class="block w-full text-center py-2 bg-white border border-green-200 text-green-700 text-xs font-black uppercase tracking-wider rounded-xl hover:bg-green-100 transition-colors">
                                    View Evaluation
                                </a>
                            @else
                                <a href="{{ route('evaluations.create', $event->id) }}" class="block w-full text-center py-2 bg-amber-500 text-white text-xs font-black uppercase tracking-wider rounded-xl hover:bg-amber-600 transition-colors shadow-sm">
                                    Submit Evaluation
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-4">
                            <p class="text-sm font-bold text-slate-700 mb-1">No Events Scheduled</p>
                            <p class="text-xs text-slate-500">Evaluation rubrics appear here once a defence is scheduled.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Write/Edit Review Modal --}}
    <div x-show="reviewModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="reviewModalOpen" x-transition.opacity @click="reviewModalOpen = false" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>
            <div x-show="reviewModalOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl p-6 sm:p-8 z-10 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-purple-600">Internal Examiner Assessment</p>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5" x-text="reviewMode === 'edit' ? 'Edit Examiner Review Note' : 'Write Examiner Review'"></h3>
                    </div>
                    <button type="button" @click="reviewModalOpen = false" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="reviewFormAction" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <template x-if="reviewMode === 'edit'">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div x-show="reviewMode === 'create'">
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Document / Version</label>
                            <select name="submission_id" x-model="reviewSubmissionId" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5">
                                <option value="">General Thesis Assessment</option>
                                @foreach($docsForJs as $docItem)
                                    <option value="{{ $docItem['id'] }}">{{ $docItem['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Assessment Category</label>
                            <select name="recommendation" x-model="reviewRecommendation" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5">
                                <option value="personal_note">Personal Review Note</option>
                                <option value="ready_for_defence">Ready for Defence</option>
                                <option value="minor_corrections">Minor Corrections Required</option>
                                <option value="major_corrections">Major Corrections Required</option>
                                <option value="approved">Approved / Satisfactory</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="stage" value="{{ $isPostDefenceStage ? 'post_defence' : 'pre_defence' }}">

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Review &amp; Observations <span class="text-rose-500">*</span></label>
                        <textarea name="content" x-model="reviewContent" rows="6" required
                                  placeholder="Write your technical observations, corrections checklist, or personal notes..."
                                  class="w-full rounded-2xl border-slate-200 text-xs font-medium text-slate-800 p-3.5"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Attach Annotated Manuscript / Review File (Optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 border border-slate-200 rounded-xl p-1.5">
                    </div>

                    @if($studentUser?->email)
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center gap-3">
                        <input id="dossier-review-send-email" type="checkbox" name="send_email" value="1" x-model="reviewSendEmail" class="rounded border-emerald-300 text-emerald-600">
                        <label for="dossier-review-send-email" class="text-xs font-semibold text-emerald-900 cursor-pointer">
                            When sending to student, also email a copy to <strong>{{ $studentUser->email }}</strong>
                        </label>
                    </div>
                    @endif

                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
                        <button type="button" @click="reviewModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">Cancel</button>
                        <button type="submit" name="action" value="save_private" class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-black uppercase tracking-wider cursor-pointer">
                            Save for Myself (Private)
                        </button>
                        <button type="submit" name="action" value="send_to_student" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider cursor-pointer shadow-md shadow-emerald-600/20">
                            Save &amp; Send to Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Message & Email Student Modal --}}
    @if($studentUser)
    <div x-show="messageModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="messageModalOpen" x-transition.opacity @click="messageModalOpen = false" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>
            <div x-show="messageModalOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl p-6 sm:p-8 z-10 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Direct Candidate Communication</p>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5">Message &amp; Email Student</h3>
                        <p class="text-xs text-slate-500 mt-0.5">To: <strong>{{ $studentUser->name }}</strong> ({{ $studentUser->email }})</p>
                    </div>
                    <button type="button" @click="messageModalOpen = false" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('examiner.theses.message', $thesis) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Subject <span class="text-rose-500">*</span></label>
                        <input type="text" name="subject" required maxlength="255" placeholder="e.g. Internal Examination Feedback" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5 px-3.5">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Delivery Channel</label>
                        <select name="delivery_method" class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 py-2.5">
                            <option value="both">Portal Inbox + Email to Student</option>
                            <option value="email">Student Email Only</option>
                            <option value="in_app">Portal Inbox Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Message <span class="text-rose-500">*</span></label>
                        <textarea name="body" rows="5" required placeholder="Write your message to the student..." class="w-full rounded-2xl border-slate-200 text-xs text-slate-800 p-3.5"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Attach File (Optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1.5">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="messageModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md shadow-emerald-600/20 cursor-pointer">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

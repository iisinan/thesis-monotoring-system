@php
    $workflow = app(\App\Services\MilestoneWorkflowService::class);
    $completed_evaluations = $completed_evaluations ?? collect();

    // Pre-compute enriched dossier records for the Internal Examiner workspace
    $dossiers = $internalTheses->map(function ($iThesis) use ($workflow, $internalPendingReviews) {
        $student = $iThesis->student ?? $iThesis->studentProfile;
        $studentUser = $student?->user;
        $milestones = ($iThesis->milestones ?? collect())->sortBy(fn($m) => $m->template?->order ?? 999)->values();

        $internalDefMilestone = $milestones->first(fn($m) => $workflow->isInternalDefence($m));
        $isPostDefenceStage = $internalDefMilestone && $workflow->isPostDefencePhase($internalDefMilestone);
        $postDefenceSub = $isPostDefenceStage ? $workflow->getPostDefenceSubmission($internalDefMilestone) : null;
        $isInternalDefApproved = $internalDefMilestone && $internalDefMilestone->status === 'approved';
        $isAwaitingApproval = $isPostDefenceStage && $postDefenceSub && !$isInternalDefApproved;
        $isAwaitingCorrectionUpload = $isPostDefenceStage && !$postDefenceSub && !$isInternalDefApproved;

        $approvals = $internalDefMilestone?->approvals ?? [];
        if (is_string($approvals)) {
            $approvals = json_decode($approvals, true) ?: [];
        }
        $ieReviewDecision = $approvals['internal_examiner_review']['decision'] ?? null;
        $isRevisedPostDefence = $isAwaitingApproval && $ieReviewDecision === 'pending_re_review';

        // Gather all submissions across milestones, newest first
        $allSubs = $milestones->flatMap(function ($m) {
            return ($m->submissions ?? collect())->map(function ($sub) use ($m) {
                $sub->setRelation('milestone', $m);
                return $sub;
            });
        })->sortByDesc('created_at')->values();

        // Determine primary manuscript, slides, and plagiarism report
        $latestManuscript = $postDefenceSub
            ?? $allSubs->first(fn($s) => in_array($s->type, ['manuscript', 'file'], true))
            ?? $allSubs->first(fn($s) => !in_array($s->type, ['ppt', 'plagiarism_report', 'publication'], true))
            ?? $allSubs->first();

        $latestPreDefenceDoc = $allSubs->first(function ($s) use ($postDefenceSub) {
            if ($postDefenceSub && $s->id === $postDefenceSub->id) return false;
            return in_array($s->type, ['manuscript', 'file'], true)
                || !in_array($s->type, ['ppt', 'plagiarism_report', 'publication'], true);
        });

        $latestPpt = $allSubs->first(fn($s) => $s->type === 'ppt');
        $latestPlagiarism = $allSubs->first(fn($s) => $s->type === 'plagiarism_report' || !empty($s->plagiarism_data));

        $internalDefEvent = ($iThesis->defenceEvents ?? collect())
            ->first(fn($e) => in_array($e->type, ['internal', 'internal_defence'], true));

        $reviews = ($iThesis->examinerReviews ?? collect())->sortByDesc('created_at')->values();
        $privateReviewsCount = $reviews->where('is_shared_with_student', false)->count();
        $sharedReviewsCount = $reviews->where('is_shared_with_student', true)->count();

        $supervisorsText = ($iThesis->assignments ?? collect())
            ->where('status', 'active')
            ->map(fn($a) => $a->supervisor?->user?->name)
            ->filter()
            ->implode(', ');

        $stageCategory = 'pre_defence';
        $stageLabel = 'Pre-Defence Review';
        $stageBadgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
        if ($iThesis->status === 'completed' || $isInternalDefApproved) {
            $stageCategory = 'completed';
            $stageLabel = $iThesis->status === 'completed' ? 'Completed' : 'Internal Defence Cleared (Viva)';
            $stageBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        } elseif ($isAwaitingApproval) {
            $stageCategory = 'post_defence_ready';
            $stageLabel = $isRevisedPostDefence ? 'Revised Correction Uploaded — Re-Review' : 'Correction Uploaded — Review & Approve';
            $stageBadgeClass = 'bg-purple-100 text-purple-800 border-purple-300';
        } elseif ($isAwaitingCorrectionUpload) {
            $stageCategory = 'awaiting_correction';
            $stageLabel = $ieReviewDecision === 'revision_required' ? 'Further Corrections Requested' : 'Presentation Passed — Awaiting Correction Upload';
            $stageBadgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
        } elseif ($internalDefMilestone && $internalDefMilestone->defence_date) {
            $stageLabel = 'Scheduled: ' . \Carbon\Carbon::parse($internalDefMilestone->defence_date)->format('M d, Y');
            $stageBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
        }

        return (object) [
            'thesis' => $iThesis,
            'student' => $student,
            'studentUser' => $studentUser,
            'milestones' => $milestones,
            'internalDefMilestone' => $internalDefMilestone,
            'internalDefEvent' => $internalDefEvent,
            'isPostDefenceStage' => $isPostDefenceStage,
            'postDefenceSub' => $postDefenceSub,
            'isInternalDefApproved' => $isInternalDefApproved,
            'isAwaitingApproval' => $isAwaitingApproval,
            'isAwaitingCorrectionUpload' => $isAwaitingCorrectionUpload,
            'isRevisedPostDefence' => $isRevisedPostDefence,
            'allSubs' => $allSubs,
            'latestManuscript' => $latestManuscript,
            'latestPreDefenceDoc' => $latestPreDefenceDoc,
            'latestPpt' => $latestPpt,
            'latestPlagiarism' => $latestPlagiarism,
            'reviews' => $reviews,
            'privateReviewsCount' => $privateReviewsCount,
            'sharedReviewsCount' => $sharedReviewsCount,
            'supervisorsText' => $supervisorsText,
            'stageCategory' => $stageCategory,
            'stageLabel' => $stageLabel,
            'stageBadgeClass' => $stageBadgeClass,
            'hasPendingAction' => $isAwaitingApproval || $internalPendingReviews->where('thesis_project_id', $iThesis->id)->isNotEmpty(),
        ];
    });

    $correctionReadyDossiers = $dossiers->where('isAwaitingApproval', true)->values();
    $awaitingUploadCount = $dossiers->where('isAwaitingCorrectionUpload', true)->count();
    $completedInternalCount = $dossiers->where('stageCategory', 'completed')->count();
    $totalReviewsCount = $dossiers->sum(fn($d) => $d->reviews->count());
    $totalPrivateReviews = $dossiers->sum('privateReviewsCount');
    $totalSharedReviews = $dossiers->sum('sharedReviewsCount');

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
    dossierSearch: '',
    statusFilter: 'all',

    // Review Composer Modal State
    reviewModalOpen: false,
    reviewMode: 'create',
    reviewFormAction: '',
    reviewThesisTitle: '',
    reviewStudentName: '',
    reviewStudentEmail: '',
    reviewStage: 'pre_defence',
    reviewSubmissionId: '',
    reviewRecommendation: 'personal_note',
    reviewContent: '',
    reviewExistingAttachment: '',
    reviewSendEmail: true,
    reviewDocs: [],

    // Post-Defence Correction Decision Modal State
    correctionModalOpen: false,
    correctionFormAction: '',
    correctionStudentName: '',
    correctionThesisTitle: '',
    correctionDocName: '',
    correctionDocUrl: '',
    correctionDocDownloadUrl: '',
    correctionDecision: 'approved',
    correctionRemarks: '',

    // Message / Email Student Modal State
    messageModalOpen: false,
    messageFormAction: '',
    messageStudentName: '',
    messageStudentEmail: '',
    messageThesisTitle: '',
    messageSubject: '',
    messageBody: '',
    messageDelivery: 'both',

    matchesDossier(title, student, email, matric, prog, category) {
        const q = (this.dossierSearch || '').toLowerCase().trim();
        const matchesText = !q ||
            title.toLowerCase().includes(q) ||
            student.toLowerCase().includes(q) ||
            email.toLowerCase().includes(q) ||
            matric.toLowerCase().includes(q) ||
            prog.toLowerCase().includes(q);
        if (!matchesText) return false;
        if (this.statusFilter !== 'all' && category !== this.statusFilter) return false;
        return true;
    },

    openReviewModal(payload) {
        this.reviewMode = payload.mode || 'create';
        this.reviewFormAction = payload.actionUrl;
        this.reviewThesisTitle = payload.thesisTitle || '';
        this.reviewStudentName = payload.studentName || '';
        this.reviewStudentEmail = payload.studentEmail || '';
        this.reviewStage = payload.stage || 'pre_defence';
        this.reviewSubmissionId = payload.submissionId || '';
        this.reviewRecommendation = payload.recommendation || 'personal_note';
        this.reviewContent = payload.content || '';
        this.reviewExistingAttachment = payload.existingAttachment || '';
        this.reviewSendEmail = true;
        this.reviewDocs = payload.docs || [];
        this.reviewModalOpen = true;
    },

    openCorrectionModal(payload) {
        this.correctionFormAction = payload.actionUrl;
        this.correctionStudentName = payload.studentName || '';
        this.correctionThesisTitle = payload.thesisTitle || '';
        this.correctionDocName = payload.docName || 'Corrected Thesis Manuscript';
        this.correctionDocUrl = payload.docUrl || '';
        this.correctionDocDownloadUrl = payload.docDownloadUrl || '';
        this.correctionDecision = 'approved';
        this.correctionRemarks = '';
        this.correctionModalOpen = true;
    },

    openMessageModal(payload) {
        this.messageFormAction = payload.actionUrl;
        this.messageStudentName = payload.studentName || '';
        this.messageStudentEmail = payload.studentEmail || '';
        this.messageThesisTitle = payload.thesisTitle || '';
        this.messageSubject = payload.defaultSubject || '';
        this.messageBody = payload.defaultBody || '';
        this.messageDelivery = 'both';
        this.messageModalOpen = true;
    }
}"
@keydown.escape.window="reviewModalOpen = false; correctionModalOpen = false; messageModalOpen = false"
class="space-y-8">

    {{-- 1. Executive Telemetry Metrics Strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black text-purple-700 bg-purple-50 px-2.5 py-1 rounded-lg uppercase tracking-wider">Assigned Portfolio</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Assigned Theses</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-black text-slate-800">{{ $dossiers->count() }}</span>
                <span class="text-xs font-bold text-purple-600">Candidates</span>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 mt-2">{{ $completedInternalCount }} cleared · {{ $dossiers->count() - $completedInternalCount }} active</p>
        </div>

        <div class="bg-white border {{ $correctionReadyDossiers->count() > 0 ? 'border-amber-200 ring-2 ring-amber-500/10' : 'border-slate-100' }} rounded-2xl p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg uppercase tracking-wider">Post-Defence</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Corrections Ready</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-black text-slate-800">{{ $correctionReadyDossiers->count() }}</span>
                <span class="text-xs font-bold text-amber-600">To Approve</span>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 mt-2">
                {{ $awaitingUploadCount > 0 ? "{$awaitingUploadCount} awaiting student upload" : ($correctionReadyDossiers->count() > 0 ? 'Uploaded & ready for review' : 'No pending corrections') }}
            </p>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg uppercase tracking-wider">Oral Panels</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Defences to Grade</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-black text-slate-800">{{ $pending_evaluations->count() }}</span>
                <span class="text-xs font-bold text-blue-600">Pending</span>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 mt-2">{{ $completed_evaluations->count() }} submitted {{ Str::plural('evaluation', $completed_evaluations->count()) }}</p>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg uppercase tracking-wider">Examiner Notes</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saved Reviews</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-black text-slate-800">{{ $totalReviewsCount }}</span>
                <span class="text-xs font-bold text-emerald-600">Written</span>
            </div>
            <p class="text-[10px] font-semibold text-slate-400 mt-2">{{ $totalPrivateReviews }} private · {{ $totalSharedReviews }} sent to students</p>
        </div>
    </div>

    {{-- 2. Priority Banner: Post-Defence Corrections Awaiting Review & Approval --}}
    @if($correctionReadyDossiers->count() > 0)
    <div class="rounded-3xl bg-gradient-to-br from-purple-900 via-slate-900 to-indigo-950 p-6 lg:p-8 text-white shadow-xl border border-purple-500/30 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/20 border border-purple-400/40 flex items-center justify-center shrink-0">
                    <span class="relative flex h-3.5 w-3.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-400"></span>
                    </span>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-purple-300">Post-Defence Verification Queue</p>
                    <h3 class="text-lg sm:text-xl font-black tracking-tight">Corrected Manuscripts Uploaded by Candidates</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Open and review each candidate's post-presentation corrections below to approve for Viva or request revisions.</p>
                </div>
            </div>
            <span class="self-start sm:self-center px-3.5 py-1.5 rounded-xl bg-amber-400 text-slate-950 text-[10px] font-black uppercase tracking-widest shrink-0">
                {{ $correctionReadyDossiers->count() }} Action {{ Str::plural('Required', $correctionReadyDossiers->count()) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($correctionReadyDossiers as $cd)
                @php
                    $cSub = $cd->postDefenceSub;
                    $cPreviewType = $detectPreviewType($cSub);
                    $cDocName = $cSub->file_meta['original_name'] ?? ('Corrected Manuscript v' . $cSub->version . '.pdf');
                @endphp
                <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-purple-300">{{ $cd->student?->student_id_number ?? 'Candidate' }} · {{ $cd->student?->program?->code ?? 'N/A' }}</p>
                            <h4 class="text-base font-black text-white truncate mt-0.5">{{ $cd->studentUser?->name ?? 'Candidate' }}</h4>
                            <p class="text-xs text-slate-300 line-clamp-1 mt-0.5" title="{{ $cd->thesis->title }}">{{ $cd->thesis->title }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 border border-emerald-400/30 text-emerald-200 text-[9px] font-black uppercase tracking-wider">
                                v{{ $cSub->version }} Uploaded
                            </span>
                            @if($cd->isRevisedPostDefence)
                                <span class="px-2 py-0.5 rounded bg-rose-500/30 border border-rose-300/40 text-rose-100 text-[8px] font-black uppercase tracking-wider">
                                    Revised Upload
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-black/25 border border-white/10 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-white truncate">{{ $cDocName }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Uploaded {{ $cSub->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button"
                                @click.prevent="$dispatch('open-document-preview', {
                                    url: '{{ route('submissions.view', $cSub) }}',
                                    title: {{ \Illuminate\Support\Js::from($cDocName) }},
                                    type: '{{ $cPreviewType }}'
                                })"
                                class="px-3 py-1.5 bg-white text-slate-900 hover:bg-purple-100 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                View Thesis
                            </button>
                            <a href="{{ route('submissions.view', [$cSub, 'download' => 1]) }}"
                               class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                                Download
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                            @click="openCorrectionModal({
                                actionUrl: '{{ route('milestones.review.update', $cd->internalDefMilestone) }}',
                                studentName: {{ \Illuminate\Support\Js::from($cd->studentUser?->name ?? 'Candidate') }},
                                thesisTitle: {{ \Illuminate\Support\Js::from($cd->thesis->title) }},
                                docName: {{ \Illuminate\Support\Js::from($cDocName) }},
                                docUrl: '{{ route('submissions.view', $cSub) }}',
                                docDownloadUrl: '{{ route('submissions.view', [$cSub, 'download' => 1]) }}'
                            })"
                            class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-lg">
                            Approve / Review Correction
                        </button>
                        @if($cd->studentUser)
                        <button type="button"
                            @click="openMessageModal({
                                actionUrl: '{{ route('examiner.theses.message', $cd->thesis) }}',
                                studentName: {{ \Illuminate\Support\Js::from($cd->studentUser->name) }},
                                studentEmail: {{ \Illuminate\Support\Js::from($cd->studentUser->email) }},
                                thesisTitle: {{ \Illuminate\Support\Js::from($cd->thesis->title) }},
                                defaultSubject: 'Regarding Your Post-Defence Corrections',
                                defaultBody: ''
                            })"
                            class="px-3.5 py-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
                            Email Student
                        </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 3. Candidate Theses Inspection & Examination Workspace --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 lg:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div class="flex items-center gap-3">
                <div class="w-2 h-7 bg-purple-600 rounded-full"></div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight uppercase">Candidate Manuscripts &amp; Examination Desk</h3>
                    <p class="text-xs text-slate-500 font-medium">Read uploaded manuscripts in a modal or download them, keep private review notes or send them to candidates, verify post-defence corrections, and email students directly.</p>
                </div>
            </div>
            <span class="self-start sm:self-center px-3.5 py-1.5 bg-purple-50 text-purple-700 rounded-xl text-xs font-black border border-purple-100 shrink-0">
                {{ $dossiers->count() }} Total {{ Str::plural('Dossier', $dossiers->count()) }}
            </span>
        </div>

        {{-- Search & Stage Filters --}}
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="dossierSearch" placeholder="Search by candidate name, email, matric ID, thesis title, or program..."
                       class="w-full pl-10 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all placeholder:text-slate-400">
                <button type="button" x-show="dossierSearch" @click="dossierSearch = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer" style="display: none;">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                <button type="button" @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                    All ({{ $dossiers->count() }})
                </button>
                <button type="button" @click="statusFilter = 'post_defence_ready'"
                        :class="statusFilter === 'post_defence_ready' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                    Corrections Ready ({{ $correctionReadyDossiers->count() }})
                </button>
                <button type="button" @click="statusFilter = 'pre_defence'"
                        :class="statusFilter === 'pre_defence' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                    Pre-Defence ({{ $dossiers->where('stageCategory', 'pre_defence')->count() }})
                </button>
                <button type="button" @click="statusFilter = 'awaiting_correction'"
                        :class="statusFilter === 'awaiting_correction' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                    Awaiting Correction ({{ $awaitingUploadCount }})
                </button>
                <button type="button" @click="statusFilter = 'completed'"
                        :class="statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'"
                        class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                    Cleared ({{ $completedInternalCount }})
                </button>
            </div>
        </div>

        {{-- Candidate Cards --}}
        <div class="space-y-6">
            @forelse($dossiers as $d)
                @php
                    $iThesis = $d->thesis;
                    $student = $d->student;
                    $studentUser = $d->studentUser;
                    $progress = $iThesis->progress_percentage;

                    $docsForJs = $d->allSubs->map(function ($s) {
                        $mName = $s->milestone?->template?->name ?? 'Milestone';
                        $fName = $s->file_meta['original_name'] ?? ('Document v' . $s->version);
                        return [
                            'id' => (string) $s->id,
                            'label' => "{$mName} — {$fName} (v{$s->version})",
                        ];
                    })->values();

                    $primarySub = $d->postDefenceSub ?? $d->latestManuscript;
                    $slidesPreviewType = $detectPreviewType($d->latestPpt);
                    $slidesDocName = $d->latestPpt ? ($d->latestPpt->file_meta['original_name'] ?? ('Presentation Slides v' . $d->latestPpt->version . '.pdf')) : '';

                    $plagSub = $d->latestPlagiarism;
                    $plagScore = $plagSub?->plagiarism_data['similarity_index']
                        ?? ($plagSub?->plagiarism_data['similarity_score'] ?? null);
                    $plagReportPath = $plagSub?->plagiarism_data['report_path'] ?? null;
                    $plagUrl = $plagSub
                        ? (($plagSub->type === 'plagiarism_report' || empty($plagReportPath)) ? route('submissions.view', $plagSub) : Storage::url($plagReportPath))
                        : null;
                @endphp

                <div x-show="matchesDossier(
                        {{ \Illuminate\Support\Js::from($iThesis->title ?? '') }},
                        {{ \Illuminate\Support\Js::from($studentUser?->name ?? '') }},
                        {{ \Illuminate\Support\Js::from($studentUser?->email ?? '') }},
                        {{ \Illuminate\Support\Js::from($student?->student_id_number ?? '') }},
                        {{ \Illuminate\Support\Js::from($student?->program?->code ?? '') }},
                        '{{ $d->stageCategory }}'
                     )"
                     x-data="{ showAllFiles: false, showAllReviews: false }"
                     class="rounded-3xl border {{ $d->isAwaitingApproval ? 'border-purple-300 bg-purple-50/20' : 'border-slate-200/80 bg-slate-50/40' }} p-5 sm:p-7 transition-all hover:shadow-md space-y-5">

                    {{-- Candidate Header & Status --}}
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center font-black text-lg shrink-0 shadow-sm">
                                {{ substr($studentUser?->name ?? '?', 0, 1) }}
                            </div>
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">{{ $studentUser?->name ?? 'Candidate' }}</h4>
                                    <span class="px-2 py-0.5 bg-slate-200/80 text-slate-700 rounded-md text-[10px] font-black uppercase tracking-wider">
                                        {{ $student?->student_id_number ?? 'N/A' }}
                                    </span>
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded-md text-[10px] font-black uppercase">
                                        {{ $student?->program?->code ?? 'N/A' }}
                                    </span>
                                    @if($student?->level)
                                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-600 rounded-md text-[10px] font-bold uppercase">
                                            {{ $student->level->name }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium">
                                    @if($studentUser?->email)
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ $studentUser->email }}
                                        </span>
                                    @endif
                                    @if($d->supervisorsText)
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            Supervisor: <strong class="text-slate-700">{{ $d->supervisorsText }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl border text-[10px] font-black uppercase tracking-wider {{ $d->stageBadgeClass }}">
                                @if($d->isAwaitingApproval)
                                    <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                                @endif
                                {{ $d->stageLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- Thesis Title, Schedule Strip & Progress --}}
                    <div class="bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Thesis Title</p>
                                <h5 class="text-sm sm:text-base font-black text-slate-900 leading-snug mt-0.5">{{ $iThesis->title ?? 'Untitled Thesis' }}</h5>
                            </div>
                            <div class="shrink-0 text-left sm:text-right">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Current Milestone</span>
                                <span class="text-xs font-black text-purple-700">{{ $iThesis->currentMilestone?->template?->name ?? 'Completed' }}</span>
                            </div>
                        </div>

                        @if($d->internalDefMilestone?->defence_date || $d->internalDefEvent)
                            <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs">
                                <div class="flex flex-wrap items-center gap-3 text-blue-900 font-semibold">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Internal Defence:
                                        <strong>
                                            {{ $d->internalDefMilestone?->defence_date ? \Carbon\Carbon::parse($d->internalDefMilestone->defence_date)->format('M d, Y') : ($d->internalDefEvent?->schedule_start ? \Carbon\Carbon::parse($d->internalDefEvent->schedule_start)->format('M d, Y') : 'Scheduled') }}
                                            @if($d->internalDefMilestone?->defence_time)
                                                · {{ \Carbon\Carbon::parse($d->internalDefMilestone->defence_time)->format('g:i A') }}
                                            @endif
                                        </strong>
                                    </span>
                                    @if($d->internalDefEvent?->location)
                                        <span class="text-blue-700">Venue: {{ $d->internalDefEvent->location }}</span>
                                    @endif
                                </div>
                                @if($d->internalDefEvent && !empty($d->internalDefEvent->online_link))
                                    <a href="{{ route('meeting.join_event', $d->internalDefEvent) }}" target="_blank"
                                       class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                                        Join Presentation
                                    </a>
                                @endif
                            </div>
                        @endif

                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[10px] font-bold">
                                <span class="text-slate-400 uppercase tracking-wider">Overall Trajectory Progress</span>
                                <span class="text-purple-700 font-black">{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-purple-600 h-full rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Two-Column Workspace: Uploaded Documents + Examiner Reviews --}}
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                        {{-- Left (7 cols): Uploaded Documents & Post-Defence Corrections --}}
                        <div class="xl:col-span-7 bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h6 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Uploaded Documents ({{ $d->allSubs->count() }})
                                </h6>
                                <div class="flex items-center gap-2">
                                    @if($plagUrl)
                                        <button type="button"
                                            @click.prevent="$dispatch('open-document-preview', {
                                                url: '{{ $plagUrl }}',
                                                title: {{ \Illuminate\Support\Js::from('Plagiarism Report - ' . ($studentUser?->name ?? 'Candidate')) }},
                                                type: 'pdf'
                                            })"
                                            class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-black uppercase tracking-wider cursor-pointer">
                                            Similarity{{ $plagScore !== null ? ': ' . $plagScore . '%' : ' Report' }}
                                        </button>
                                    @endif
                                    @if($d->allSubs->count() > 1)
                                        <button type="button" @click="showAllFiles = !showAllFiles"
                                                class="text-[10px] font-black uppercase tracking-wider text-purple-600 hover:text-purple-800 cursor-pointer">
                                            <span x-text="showAllFiles ? 'Hide Older Files' : 'All Uploads ({{ $d->allSubs->count() }})'"></span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($d->postDefenceSub)
                                @php
                                    $postSub = $d->postDefenceSub;
                                    $postType = $detectPreviewType($postSub);
                                    $postName = $postSub->file_meta['original_name'] ?? ('Corrected Thesis v' . $postSub->version . '.pdf');
                                @endphp
                                <div class="p-4 rounded-2xl bg-purple-50 border-2 border-purple-200 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded bg-purple-600 text-white text-[9px] font-black uppercase tracking-wider">Post-Defence Corrected Manuscript</span>
                                                <span class="text-[10px] font-bold text-purple-800">v.0{{ $postSub->version }}</span>
                                                @if($d->isRevisedPostDefence)
                                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200 text-[9px] font-black uppercase">Revised Upload</span>
                                                @endif
                                            </div>
                                            <p class="text-xs font-black text-slate-900 truncate mt-1.5">{{ $postName }}</p>
                                            <p class="text-[10px] text-slate-500 mt-0.5">Uploaded {{ $postSub->created_at?->format('M d, Y \a\t g:i A') }} ({{ $postSub->created_at?->diffForHumans() }})</p>
                                            @if($postSub->description)
                                                <p class="text-[11px] text-purple-900/80 italic mt-1">"{{ $postSub->description }}"</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button"
                                                @click.prevent="$dispatch('open-document-preview', {
                                                    url: '{{ route('submissions.view', $postSub) }}',
                                                    title: {{ \Illuminate\Support\Js::from($postName) }},
                                                    type: '{{ $postType }}'
                                                })"
                                                class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-xs transition-all cursor-pointer">
                                                View Thesis
                                            </button>
                                            <a href="{{ route('submissions.view', [$postSub, 'download' => 1]) }}"
                                               class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-purple-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                                Download
                                            </a>
                                        </div>
                                    </div>

                                    @if(!$d->isInternalDefApproved)
                                        <div class="pt-3 border-t border-purple-200/70 flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-[10px] font-bold text-purple-800">Verify corrections and advance candidate to Viva Voce or request changes.</span>
                                            <button type="button"
                                                @click="openCorrectionModal({
                                                    actionUrl: '{{ route('milestones.review.update', $d->internalDefMilestone) }}',
                                                    studentName: {{ \Illuminate\Support\Js::from($studentUser?->name ?? 'Candidate') }},
                                                    thesisTitle: {{ \Illuminate\Support\Js::from($iThesis->title) }},
                                                    docName: {{ \Illuminate\Support\Js::from($postName) }},
                                                    docUrl: '{{ route('submissions.view', $postSub) }}',
                                                    docDownloadUrl: '{{ route('submissions.view', [$postSub, 'download' => 1]) }}'
                                                })"
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                                Approve / Review Correction
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @elseif($d->isAwaitingCorrectionUpload)
                                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <span class="px-2 py-0.5 rounded bg-amber-200/80 text-amber-900 text-[9px] font-black uppercase tracking-wider">Post-Defence Stage</span>
                                        <p class="text-xs font-bold text-amber-950 mt-1">Presentation concluded — awaiting student's corrected manuscript upload.</p>
                                    </div>
                                    @if($studentUser)
                                        <button type="button"
                                            @click="openMessageModal({
                                                actionUrl: '{{ route('examiner.theses.message', $iThesis) }}',
                                                studentName: {{ \Illuminate\Support\Js::from($studentUser->name) }},
                                                studentEmail: {{ \Illuminate\Support\Js::from($studentUser->email) }},
                                                thesisTitle: {{ \Illuminate\Support\Js::from($iThesis->title) }},
                                                defaultSubject: 'Reminder: Upload Post-Defence Corrected Thesis Manuscript',
                                                defaultBody: 'Dear {{ addslashes($studentUser->name) }},\n\nFollowing your Internal Defence presentation, please remember to upload your corrected thesis manuscript on the Internal Defence milestone page so it can be reviewed and approved for Viva Voce.\n\nRegards,\nInternal Examiner'
                                            })"
                                            class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shrink-0 cursor-pointer">
                                            Remind Student
                                        </button>
                                    @endif
                                </div>
                            @endif

                            {{-- Latest Pre-Defence / Active Manuscript --}}
                            @php
                                $displaySub = $d->postDefenceSub ? $d->latestPreDefenceDoc : $d->latestManuscript;
                            @endphp
                            @if($displaySub)
                                @php
                                    $dispType = $detectPreviewType($displaySub);
                                    $dispName = $displaySub->file_meta['original_name'] ?? ('Thesis Manuscript v' . $displaySub->version . '.pdf');
                                    $dispMilestone = $displaySub->milestone?->template?->name ?? 'Manuscript';
                                @endphp
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[9px] font-black uppercase tracking-wider">{{ $dispMilestone }}</span>
                                            <span class="text-[10px] font-bold text-slate-500">v.0{{ $displaySub->version }}</span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-900 truncate mt-1">{{ $dispName }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Uploaded {{ $displaySub->created_at?->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button"
                                            @click.prevent="$dispatch('open-document-preview', {
                                                url: '{{ route('submissions.view', $displaySub) }}',
                                                title: {{ \Illuminate\Support\Js::from($dispName) }},
                                                type: '{{ $dispType }}'
                                            })"
                                            class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                            View Thesis
                                        </button>
                                        <a href="{{ route('submissions.view', [$displaySub, 'download' => 1]) }}"
                                           class="px-3 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @elseif(!$d->postDefenceSub)
                                <div class="p-5 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center">
                                    <p class="text-xs font-semibold text-slate-400">No thesis manuscript has been uploaded by the student yet.</p>
                                </div>
                            @endif

                            {{-- Latest Slides if separate --}}
                            @if($d->latestPpt && (!$primarySub || $d->latestPpt->id !== $primarySub->id))
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[9px] font-black uppercase tracking-wider">Presentation Slides</span>
                                        <p class="text-xs font-bold text-slate-800 truncate mt-1">{{ $slidesDocName }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button"
                                            @click.prevent="$dispatch('open-document-preview', {
                                                url: '{{ route('submissions.view', $d->latestPpt) }}',
                                                title: {{ \Illuminate\Support\Js::from($slidesDocName) }},
                                                type: '{{ $slidesPreviewType }}'
                                            })"
                                            class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 rounded-lg text-[10px] font-black uppercase tracking-wider cursor-pointer">
                                            Open Slides
                                        </button>
                                        <a href="{{ route('submissions.view', [$d->latestPpt, 'download' => 1]) }}"
                                           class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endif

                            {{-- Expandable Full Document History --}}
                            @if($d->allSubs->count() > 1)
                                <div x-show="showAllFiles" x-cloak class="space-y-2 pt-2 border-t border-slate-100">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Complete Submission History</p>
                                    <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                                        @foreach($d->allSubs as $histSub)
                                            @php
                                                $hType = $detectPreviewType($histSub);
                                                $hName = $histSub->file_meta['original_name'] ?? ('Submission v' . $histSub->version);
                                            @endphp
                                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between gap-2 text-xs">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-800 truncate">{{ $hName }}</p>
                                                    <p class="text-[10px] text-slate-400">
                                                        {{ $histSub->milestone?->template?->name ?? 'Milestone' }} · v{{ $histSub->version }} · {{ $histSub->created_at?->format('M d, Y') }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <button type="button"
                                                        @click.prevent="$dispatch('open-document-preview', {
                                                            url: '{{ route('submissions.view', $histSub) }}',
                                                            title: {{ \Illuminate\Support\Js::from($hName) }},
                                                            type: '{{ $hType }}'
                                                        })"
                                                        class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-900 hover:text-white text-slate-700 rounded-lg text-[9px] font-black uppercase cursor-pointer transition-colors">
                                                        Open
                                                    </button>
                                                    <a href="{{ route('submissions.view', [$histSub, 'download' => 1]) }}"
                                                       class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-lg text-[9px] font-black uppercase transition-colors">
                                                        Save
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Right (5 cols): Examiner Reviews (Private vs Shared with Student) --}}
                        <div class="xl:col-span-5 bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 flex flex-col justify-between space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <h6 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Your Review Notes ({{ $d->reviews->count() }})
                                    </h6>
                                    <button type="button"
                                        @click="openReviewModal({
                                            mode: 'create',
                                            actionUrl: '{{ route('examiner.reviews.store', $iThesis) }}',
                                            thesisTitle: {{ \Illuminate\Support\Js::from($iThesis->title) }},
                                            studentName: {{ \Illuminate\Support\Js::from($studentUser?->name ?? 'Candidate') }},
                                            studentEmail: {{ \Illuminate\Support\Js::from($studentUser?->email ?? '') }},
                                            stage: '{{ $d->isPostDefenceStage ? 'post_defence' : 'pre_defence' }}',
                                            submissionId: '{{ $primarySub?->id ?? '' }}',
                                            recommendation: 'personal_note',
                                            content: '',
                                            existingAttachment: '',
                                            docs: {{ \Illuminate\Support\Js::from($docsForJs) }}
                                        })"
                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                        + Write Review
                                    </button>
                                </div>

                                @forelse($d->reviews as $idx => $rev)
                                    <div x-show="showAllReviews || {{ $idx }} < 2"
                                         class="p-3.5 rounded-2xl border {{ $rev->is_shared_with_student ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200' }} space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-1.5">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @if($rev->is_shared_with_student)
                                                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase tracking-wider">
                                                        Sent to Student{{ $rev->sent_via_email ? ' · Emailed' : '' }}
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-white text-[9px] font-black uppercase tracking-wider">
                                                        Private (Only You)
                                                    </span>
                                                @endif
                                                <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-600 text-[9px] font-bold">
                                                    {{ $rev->recommendation_label }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium">{{ $rev->updated_at?->diffForHumans() }}</span>
                                        </div>

                                        <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed line-clamp-3">{{ $rev->content }}</p>

                                        @if($rev->attachment_path)
                                            <div class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl bg-white border border-slate-200/80 text-[10px]">
                                                <span class="font-bold text-slate-700 truncate">📎 {{ $rev->attachment_name ?: 'Review Attachment' }}</span>
                                                <a href="{{ route('examiner.reviews.attachment', [$rev, 'download' => 1]) }}" class="font-black text-purple-600 hover:text-purple-800 uppercase shrink-0">Download</a>
                                            </div>
                                        @endif

                                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    @click="openReviewModal({
                                                        mode: 'edit',
                                                        actionUrl: '{{ route('examiner.reviews.update', $rev) }}',
                                                        thesisTitle: {{ \Illuminate\Support\Js::from($iThesis->title) }},
                                                        studentName: {{ \Illuminate\Support\Js::from($studentUser?->name ?? 'Candidate') }},
                                                        studentEmail: {{ \Illuminate\Support\Js::from($studentUser?->email ?? '') }},
                                                        stage: '{{ $rev->stage }}',
                                                        submissionId: '{{ $rev->submission_id ?? '' }}',
                                                        recommendation: '{{ $rev->recommendation ?? 'personal_note' }}',
                                                        content: {{ \Illuminate\Support\Js::from($rev->content) }},
                                                        existingAttachment: {{ \Illuminate\Support\Js::from($rev->attachment_name ?? '') }},
                                                        docs: {{ \Illuminate\Support\Js::from($docsForJs) }}
                                                    })"
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
                                                    <button type="submit" class="text-[10px] font-black uppercase tracking-wider text-rose-500 hover:text-rose-700 cursor-pointer">
                                                        Delete
                                                    </button>
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
                                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[9px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                                        Send to Student
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-5 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center space-y-1.5">
                                        <p class="text-xs font-bold text-slate-500">No review notes saved yet.</p>
                                        <p class="text-[11px] text-slate-400">Write private notes for yourself or send formal review feedback directly to the candidate.</p>
                                    </div>
                                @endforelse

                                @if($d->reviews->count() > 2)
                                    <button type="button" @click="showAllReviews = !showAllReviews"
                                            class="text-[10px] font-black uppercase tracking-wider text-purple-600 hover:text-purple-800 cursor-pointer">
                                        <span x-text="showAllReviews ? 'Show Fewer Reviews' : 'Show All {{ $d->reviews->count() }} Reviews'"></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Action Footer --}}
                    <div class="pt-3 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($studentUser)
                                <button type="button"
                                    @click="openMessageModal({
                                        actionUrl: '{{ route('examiner.theses.message', $iThesis) }}',
                                        studentName: {{ \Illuminate\Support\Js::from($studentUser->name) }},
                                        studentEmail: {{ \Illuminate\Support\Js::from($studentUser->email) }},
                                        thesisTitle: {{ \Illuminate\Support\Js::from($iThesis->title) }},
                                        defaultSubject: '',
                                        defaultBody: ''
                                    })"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer shadow-2xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Message &amp; Email Student
                                </button>
                                <a href="{{ route('inbox.index', ['user_id' => $studentUser->id]) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                    Open Inbox Thread
                                </a>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('examiner.theses.show', $iThesis->id) }}"
                               class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                Full Dossier
                            </a>
                            <a href="{{ route('milestones.index', ['thesis_id' => $iThesis->id, 'expanded' => $d->internalDefMilestone?->id ?? $iThesis->currentMilestone?->id]) }}"
                               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all shadow-xs">
                                Milestones Workspace
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-14 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No Internal Examiner Theses Assigned</p>
                    <p class="text-xs text-slate-400 mt-1">Assigned candidates and their manuscripts will appear here automatically.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- 4. Oral Defence Evaluations Queue (Pending & Submitted) --}}
    @if($pending_evaluations->count() > 0 || $completed_evaluations->count() > 0)
    <div class="bg-white rounded-3xl border border-blue-100 shadow-sm p-6 lg:p-8 space-y-6">
        @if($pending_evaluations->count() > 0)
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                        <h3 class="text-base font-black text-slate-800 tracking-tight uppercase">Pending Oral Defence Evaluations</h3>
                    </div>
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 text-[10px] font-black rounded-xl uppercase border border-blue-100">
                        {{ $pending_evaluations->count() }} Pending
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pending_evaluations as $event)
                    <div class="p-5 bg-blue-50/50 border border-blue-100 rounded-2xl space-y-3 hover:border-blue-300 transition-all">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[9px] font-black uppercase tracking-widest text-blue-700 bg-blue-100 px-2 py-0.5 rounded">{{ ucfirst(str_replace('_', ' ', $event->type ?? 'Internal')) }} Defence</span>
                                <span class="text-[10px] font-bold text-slate-500">{{ $event->schedule_start ? \Carbon\Carbon::parse($event->schedule_start)->format('M d, Y') : 'Scheduled' }}</span>
                            </div>
                            <h4 class="text-sm font-black text-slate-900 mt-2 truncate">{{ $event->thesis?->student?->user?->name ?? 'Candidate' }}</h4>
                            <p class="text-xs text-slate-500 truncate mt-0.5">{{ $event->thesis?->title ?? 'Untitled Thesis' }}</p>
                        </div>
                        <a href="{{ route('evaluations.create', ['defenceEvent' => $event->id]) }}" class="block text-center py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-xs">
                            Grade Oral Defence
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($completed_evaluations->count() > 0)
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 bg-emerald-600 rounded-full"></div>
                        <h3 class="text-base font-black text-slate-800 tracking-tight uppercase">Submitted Evaluation Records</h3>
                    </div>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-xl uppercase border border-emerald-100">
                        {{ $completed_evaluations->count() }} Completed
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($completed_evaluations as $eval)
                    <div class="p-4 bg-emerald-50/40 border border-emerald-100 rounded-2xl space-y-3">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">{{ ucfirst(str_replace('_', ' ', $eval->defenceEvent?->type ?? 'Defence')) }}</span>
                                <span class="text-[10px] font-bold text-slate-400">{{ $eval->submitted_at?->format('M d, Y') ?? $eval->created_at?->format('M d, Y') }}</span>
                            </div>
                            <h4 class="text-sm font-black text-slate-900 mt-2 truncate">{{ $eval->defenceEvent?->thesis?->student?->user?->name ?? 'Candidate' }}</h4>
                            <p class="text-xs text-slate-500 truncate mt-0.5">{{ $eval->defenceEvent?->thesis?->title ?? 'Thesis' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('evaluations.show', $eval) }}" class="flex-1 text-center py-2 bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                View Grade
                            </a>
                            <a href="{{ route('evaluations.pdf', $eval) }}" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all">
                                PDF
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- ================================================================= --}}
    {{-- MODAL 1: Write / Edit Examiner Review (Private OR Send to Student) --}}
    {{-- ================================================================= --}}
    <div x-show="reviewModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="reviewModalOpen" x-transition.opacity @click="reviewModalOpen = false" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

            <div x-show="reviewModalOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl p-6 sm:p-8 z-10 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-purple-600">Internal Examiner Assessment</p>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5" x-text="reviewMode === 'edit' ? 'Edit Examiner Review Note' : 'Write Examiner Review'"></h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Candidate: <strong class="text-slate-800" x-text="reviewStudentName"></strong>
                            <span x-show="reviewThesisTitle" class="text-slate-400"> · <span x-text="reviewThesisTitle"></span></span>
                        </p>
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
                            <select name="submission_id" x-model="reviewSubmissionId" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">General Thesis Assessment</option>
                                <template x-for="doc in reviewDocs" :key="doc.id">
                                    <option :value="doc.id" x-text="doc.label"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Assessment Category</label>
                            <select name="recommendation" x-model="reviewRecommendation" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5 focus:ring-purple-500 focus:border-purple-500">
                                <option value="personal_note">Personal Review Note</option>
                                <option value="ready_for_defence">Ready for Defence</option>
                                <option value="minor_corrections">Minor Corrections Required</option>
                                <option value="major_corrections">Major Corrections Required</option>
                                <option value="approved">Approved / Satisfactory</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="stage" :value="reviewStage">

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Review &amp; Observations <span class="text-rose-500">*</span></label>
                        <textarea name="content" x-model="reviewContent" rows="6" required
                                  placeholder="Write your technical observations, corrections checklist, or personal examination notes here..."
                                  class="w-full rounded-2xl border-slate-200 text-xs font-medium text-slate-800 p-3.5 focus:ring-purple-500 focus:border-purple-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Attach Annotated Manuscript / Review File (Optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 border border-slate-200 rounded-xl p-1.5">
                        <p x-show="reviewExistingAttachment" class="text-[11px] text-slate-500 mt-1">
                            Current attachment: <strong x-text="reviewExistingAttachment"></strong> (upload a new file to replace)
                        </p>
                    </div>

                    <div x-show="reviewStudentEmail" class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center gap-3">
                        <input id="review-send-email" type="checkbox" name="send_email" value="1" x-model="reviewSendEmail"
                               class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="review-send-email" class="text-xs font-semibold text-emerald-900 cursor-pointer">
                            When sending to student, also email a copy to <strong x-text="reviewStudentEmail"></strong>
                        </label>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
                        <button type="button" @click="reviewModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" name="action" value="save_private"
                                class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                            Save for Myself (Private)
                        </button>
                        <button type="submit" name="action" value="send_to_student"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer shadow-md shadow-emerald-600/20">
                            Save &amp; Send to Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- MODAL 2: Post-Defence Correction Verification & Clearance         --}}
    {{-- ================================================================= --}}
    <div x-show="correctionModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="correctionModalOpen" x-transition.opacity @click="correctionModalOpen = false" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

            <div x-show="correctionModalOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl p-6 sm:p-8 z-10 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-purple-600">Internal Defence Clearance</p>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5">Verify Post-Defence Corrections</h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="correctionStudentName + ' — ' + correctionThesisTitle"></p>
                    </div>
                    <button type="button" @click="correctionModalOpen = false" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-3.5 rounded-2xl bg-purple-50 border border-purple-200 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-wider text-purple-700">Submitted Correction</p>
                        <p class="text-xs font-bold text-slate-900 truncate" x-text="correctionDocName"></p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button"
                            @click="$dispatch('open-document-preview', { url: correctionDocUrl, title: correctionDocName, type: 'pdf' })"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[10px] font-black uppercase cursor-pointer">
                            Open PDF
                        </button>
                        <a :href="correctionDocDownloadUrl" class="px-3 py-1.5 bg-white border border-purple-200 text-slate-700 hover:bg-slate-50 rounded-lg text-[10px] font-black uppercase">
                            Download
                        </a>
                    </div>
                </div>

                <form :action="correctionFormAction" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Examiner Decision</label>
                        <select name="decision" x-model="correctionDecision" required class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 py-2.5 focus:ring-purple-500 focus:border-purple-500">
                            <option value="approved">Approve Corrected Thesis &amp; Advance Candidate to Viva</option>
                            <option value="revision_required">Request Further Corrections</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Verification Remarks <span class="text-rose-500">*</span></label>
                        <textarea name="remarks" x-model="correctionRemarks" rows="4" required
                                  placeholder="Confirm that all panel corrections were addressed, or specify remaining changes required..."
                                  class="w-full rounded-2xl border-slate-200 text-xs text-slate-800 p-3.5 focus:ring-purple-500 focus:border-purple-500"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="correctionModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md shadow-purple-600/20 cursor-pointer">
                            Submit Decision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- MODAL 3: Direct Message & Email to Student                        --}}
    {{-- ================================================================= --}}
    <div x-show="messageModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="messageModalOpen" x-transition.opacity @click="messageModalOpen = false" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs"></div>

            <div x-show="messageModalOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl p-6 sm:p-8 z-10 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Direct Candidate Communication</p>
                        <h3 class="text-lg font-black text-slate-900 mt-0.5">Message &amp; Email Student</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            To: <strong class="text-slate-800" x-text="messageStudentName"></strong>
                            (<span class="text-emerald-700 font-semibold" x-text="messageStudentEmail"></span>)
                        </p>
                    </div>
                    <button type="button" @click="messageModalOpen = false" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="messageFormAction" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Subject <span class="text-rose-500">*</span></label>
                        <input type="text" name="subject" x-model="messageSubject" required maxlength="255"
                               placeholder="e.g. Internal Examination Feedback & Clarification"
                               class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 py-2.5 px-3.5 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Delivery Channel</label>
                        <select name="delivery_method" x-model="messageDelivery" class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 py-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="both">Portal Inbox + Email to Student</option>
                            <option value="email">Student Email Only</option>
                            <option value="in_app">Portal Inbox Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Message <span class="text-rose-500">*</span></label>
                        <textarea name="body" x-model="messageBody" rows="5" required
                                  placeholder="Write your message to the student..."
                                  class="w-full rounded-2xl border-slate-200 text-xs text-slate-800 p-3.5 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-600 mb-1.5">Attach File (Optional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-xl p-1.5">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="messageModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md shadow-emerald-600/20 cursor-pointer">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

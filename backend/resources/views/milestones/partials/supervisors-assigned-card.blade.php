@php
    $student = $milestone->thesis?->student;
    $studentUser = $student?->user;
    $studentName = $studentUser?->name ?? 'Candidate';
    $matricNumber = $student?->student_id_number ?? 'N/A';
    $program = $student?->program;
    $programName = $program?->name ?? 'Academic Program';
    $levelName = strtoupper($student?->level?->name ?? '');
    $isPhD = str_contains($levelName, 'PHD');
    $requiredSupervisorCount = $isPhD ? 3 : 2;

    $submissions = $milestone->submissions->sortByDesc('created_at');
    $latestProposal = $submissions->first();
    $hasUploaded = !is_null($latestProposal);
    $proposalUrl = $latestProposal ? (str_starts_with($latestProposal->file_url, 'http') ? $latestProposal->file_url : \Illuminate\Support\Facades\Storage::url($latestProposal->file_url)) : '';

    $supervisorRequest = $milestone->approvals['supervisor_request'] ?? null;
    $hasSelfInserted = !empty($milestone->approvals['student_inserted_supervisors']);
    $hasRequestedSupervisors = !empty($supervisorRequest);

    $assignments = $milestone->thesis?->assignments()->where('status', 'active')->with('supervisor.user')->get() ?? collect();
    $hasSupervisors = $assignments->count() > 0;

    // Available supervisors: Students & Admins see all directory faculty (program-matching & Professors first); Coordinators see scoped program faculty
    if (auth()->user()->hasAnyRole(['Student', 'Admin'])) {
        $availableSupervisors = \App\Models\SupervisorProfile::with(['user', 'programs'])
            ->whereHas('user', fn($q) => $q->where('id', '!=', $student?->user_id ?? auth()->id()))
            ->get()
            ->sortBy(function ($s) use ($student) {
                $inProg = ($student?->program_id && $s->programs->contains('id', $student->program_id)) ? 0 : 1;
                $isProf = stripos($s->rank ?? '', 'prof') !== false ? 0 : 1;
                return "{$inProg}-{$isProf}-" . strtolower($s->user?->name ?? 'zzz');
            })->values();
    } else {
        $availableSupervisors = \App\Models\SupervisorProfile::with('user')
            ->when($student?->program_id, function($q) use ($student) {
                $q->whereHas('programs', fn($p) => $p->where('programs.id', $student->program_id));
            })
            ->get();

        if ($availableSupervisors->isEmpty()) {
            $availableSupervisors = \App\Models\SupervisorProfile::with('user')->get();
        }
    }

    $allSupervisorsJson = $availableSupervisors->map(function($s) {
        return [
            'id' => $s->id,
            'name' => $s->user?->name ?? 'Supervisor',
            'email' => $s->user?->email ?? '',
            'rank' => $s->rank ?? 'Senior Lecturer',
            'is_professor' => strtoupper($s->rank ?? '') === 'PROFESSOR',
            'current_load' => (int) ($s->current_load ?? 0),
            'max_students' => (int) ($s->max_students ?? 5),
            'department' => $s->department ?? '',
        ];
    })->values();

    $assignedIds = $assignments->pluck('supervisor_profile_id')->all();
    $primaryAssignment = $assignments->firstWhere('role', 'primary') ?? $assignments->first();
    $leadSupervisorId = $primaryAssignment?->supervisor_profile_id ?? ($assignedIds[0] ?? '');

    $secondaryAssignments = $assignments->filter(fn($a) => $a->id !== $primaryAssignment?->id)->values();
    $secondSupervisorId = $secondaryAssignments->get(0)?->supervisor_profile_id ?? ($assignedIds[1] ?? '');
    $thirdSupervisorId = $secondaryAssignments->get(1)?->supervisor_profile_id ?? ($assignedIds[2] ?? '');

    $defaultStudentPath = ($errors->has('supervisor_ids.*') || $errors->has('new_supervisors.*') || $errors->has('supervisor_ids') || old('supervisor_ids') || old('new_supervisors'))
        ? 'insert'
        : 'request';
@endphp

<div id="supervisors-assigned-card-{{ $milestone->id }}" 
     x-data="{
        showAssignModal: false,
        showStudentMessageModal: false,
        messageRecipient: '{{ addslashes($studentName) }}',
        uploading: false,
        submittingSelfSups: false,
        requestingSups: false,
        fileError: '',
        studentPath: '{{ $defaultStudentPath }}',
        manualMode: [
            {{ (old('new_supervisors.0.name') || old('new_supervisors.0.email')) ? 'true' : 'false' }},
            {{ (old('new_supervisors.1.name') || old('new_supervisors.1.email')) ? 'true' : 'false' }},
            {{ (old('new_supervisors.2.name') || old('new_supervisors.2.email')) ? 'true' : 'false' }}
        ],
        slotSearch: ['', '', ''],
        selectedLead: '{{ $leadSupervisorId }}',
        selectedSecond: '{{ $secondSupervisorId }}',
        selectedThird: '{{ $thirdSupervisorId }}',
        supervisorsList: {{ \Illuminate\Support\Js::from($allSupervisorsJson) }},
        isPhD: {{ $isPhD ? 'true' : 'false' }},
        randomizeAlert: '',
        filteredSupervisors(slotIdx) {
            const q = (this.slotSearch[slotIdx] || '').toLowerCase().trim();
            if (!q) return this.supervisorsList;
            return this.supervisorsList.filter(s =>
                (s.name || '').toLowerCase().includes(q) ||
                (s.email || '').toLowerCase().includes(q) ||
                (s.rank || '').toLowerCase().includes(q) ||
                (s.department || '').toLowerCase().includes(q)
            );
        },
        randomize() {
            const profs = this.supervisorsList.filter(s => s.is_professor && s.current_load < s.max_students);
            const allEligible = this.supervisorsList.filter(s => s.current_load < s.max_students);
            
            const availableProfs = profs.length > 0 ? profs : this.supervisorsList.filter(s => s.is_professor);
            if (availableProfs.length === 0) {
                alert('No Professors found for this program. Institutional guidelines require a Professor as Lead Supervisor.');
                return;
            }
            
            // Pick a random Professor for Lead
            const randomProf = availableProfs[Math.floor(Math.random() * availableProfs.length)];
            this.selectedLead = randomProf.id;
            
            // Pick secondary supervisors (different from lead)
            const remaining = allEligible.filter(s => s.id !== this.selectedLead);
            const pool = remaining.length >= (this.isPhD ? 2 : 1) ? remaining : this.supervisorsList.filter(s => s.id !== this.selectedLead);
            
            if (pool.length > 0) {
                const shuffled = [...pool].sort(() => 0.5 - Math.random());
                this.selectedSecond = shuffled[0]?.id || '';
                if (this.isPhD && shuffled.length > 1) {
                    this.selectedThird = shuffled[1]?.id || '';
                }
            }
            
            this.randomizeAlert = 'Supervisors randomized successfully based on academic rank and workload!';
            setTimeout(() => { this.randomizeAlert = ''; }, 4500);
        }
     }" 
     class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- 1. STUDENT SIDE VIEW                                                      --}}
    {{-- ========================================================================= --}}
    @if(auth()->user()->hasRole('Student'))
        @if($hasSupervisors)
            {{-- State 1C: Supervisors are already assigned / recorded --}}
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-emerald-50 via-teal-50/50 to-white rounded-[2.5rem] border border-emerald-200 shadow-xl shadow-slate-200/40 p-8 sm:p-10 relative overflow-hidden animate-in-up">
                    <div class="relative flex items-start gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/25">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="space-y-2 max-w-3xl">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider border border-emerald-200">
                                <span>{{ $hasSelfInserted ? 'Supervisors Recorded' : 'Supervisors Assigned' }}</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
                                Your supervisory panel is active ({{ $assignments->count() }} {{ \Illuminate\Support\Str::plural('supervisor', $assignments->count()) }}).
                            </h2>
                            <p class="text-xs sm:text-sm font-medium text-slate-600 leading-relaxed">
                                {{ $hasSelfInserted ? 'You recorded your existing supervisory panel on this milestone.' : 'Your Program Coordinator has assigned your supervisory committee.' }}
                            </p>
                        </div>
                    </div>
                </div>

                @if($hasUploaded && $latestProposal)
                    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                                Uploaded Tentative Proposal
                            </h3>
                            <span class="text-xs font-bold text-slate-400">
                                Version 0{{ $latestProposal->version }} • {{ $latestProposal->created_at->format('M d, Y') }}
                            </span>
                        </div>
                        <div class="p-5 bg-slate-50 border border-slate-100 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0 font-black text-xs">PDF</div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">{{ $latestProposal->file_meta['original_name'] ?? 'Tentative_Research_Proposal.pdf' }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Submitted {{ $latestProposal->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click.prevent="$dispatch('open-document-preview', { url: '{{ $proposalUrl }}', title: 'Tentative Proposal - {{ addslashes($studentName) }}', type: 'pdf' })"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold uppercase tracking-wider border border-indigo-200 cursor-pointer">
                                    <span>Preview Document</span>
                                </button>
                                <a href="{{ $proposalUrl }}" download class="p-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl" title="Download">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-8">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                        Supervisory Committee Allocation
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($assignments as $assignment)
                            @php $sup = $assignment->supervisor; @endphp
                            <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-indigo-600 font-black text-sm shadow-sm">
                                    {{ substr($sup->user?->name ?? 'S', 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ $assignment->role === 'primary' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $assignment->role === 'primary' ? 'Lead Supervisor' : 'Co-Supervisor' }}
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400">{{ $sup->rank }}</span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 truncate mt-0.5">{{ $sup->user?->name }}</h4>
                                    <p class="text-xs text-slate-500 truncate">{{ $sup->user?->email ?? ($sup->department ?? $programName) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            {{-- State 1A / 1B: Student does NOT yet have supervisors on portal -> Choose Insert Supervisors OR Upload Tentative Proposal & Request --}}
            <div class="space-y-6 animate-in-up">
                {{-- Two-Path Selector Header --}}
                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black uppercase tracking-wider mb-2 border border-indigo-100">
                                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                                <span>Supervisory Panel Setup</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Do you already have supervisors, or do you need supervisors assigned?
                            </h2>
                            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-1">
                                Candidate: <strong class="text-slate-900">{{ $studentName }}</strong> &bull; Matric No: <strong class="text-slate-900">{{ $matricNumber }}</strong> &bull; Required Panel: <strong>{{ $requiredSupervisorCount }} Supervisors</strong> ({{ $isPhD ? 'PhD' : 'MSc' }})
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button type="button"
                                @click="studentPath = 'insert'"
                                :class="studentPath === 'insert'
                                    ? 'border-indigo-600 bg-indigo-50/70 ring-2 ring-indigo-500/20 shadow-md'
                                    : 'border-slate-200 bg-slate-50/60 hover:border-indigo-300 hover:bg-white'"
                                class="text-left p-5 rounded-3xl border-2 transition-all cursor-pointer flex items-start gap-4">
                            <div :class="studentPath === 'insert' ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 border border-slate-200'"
                                 class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Option 1</span>
                                <h3 class="text-sm sm:text-base font-black text-slate-900 mt-0.5">I Already Have Supervisors</h3>
                                <p class="text-xs font-medium text-slate-600 mt-1">
                                    Insert your {{ $requiredSupervisorCount }} existing supervisors directly to complete this step and unlock Proposal Defence.
                                </p>
                            </div>
                        </button>

                        <button type="button"
                                @click="studentPath = 'request'"
                                :class="studentPath === 'request'
                                    ? 'border-emerald-600 bg-emerald-50/70 ring-2 ring-emerald-500/20 shadow-md'
                                    : 'border-slate-200 bg-slate-50/60 hover:border-emerald-300 hover:bg-white'"
                                class="text-left p-5 rounded-3xl border-2 transition-all cursor-pointer flex items-start gap-4">
                            <div :class="studentPath === 'request' ? 'bg-emerald-600 text-white' : 'bg-white text-emerald-600 border border-slate-200'"
                                 class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Option 2</span>
                                <h3 class="text-sm sm:text-base font-black text-slate-900 mt-0.5">Upload Tentative Proposal &amp; Request Supervisors</h3>
                                <p class="text-xs font-medium text-slate-600 mt-1">
                                    Upload your tentative proposal PDF and immediately notify your Program Coordinator by email with your name &amp; matric number.
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- OPTION 1 PANEL: Student Inserts Existing Supervisors --}}
                <div x-show="studentPath === 'insert'" x-cloak class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-10 space-y-6">
                    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900">Insert Your Supervisory Panel</h3>
                            <p class="text-xs sm:text-sm text-slate-600 font-medium mt-1">
                                Select each of your {{ $requiredSupervisorCount }} supervisors from the faculty directory, or switch a slot to manual entry if a supervisor is not listed yet.
                            </p>
                        </div>
                    </div>

                    @if($errors->has('supervisor_ids') || $errors->has('supervisor_ids.*') || $errors->has('new_supervisors.*'))
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700 space-y-1">
                            @foreach($errors->all() as $err)
                                <p>&bull; {{ $err }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('milestones.supervisors.store', $milestone) }}" method="POST" @submit="submittingSelfSups = true" class="space-y-5">
                        @csrf
                        @for($i = 0; $i < $requiredSupervisorCount; $i++)
                            @php
                                $slotTitle = $i === 0 ? 'Lead Supervisor (Main Supervisor — Professor Recommended)' : ($isPhD ? "Co-Supervisor {$i}" : 'Co-Supervisor');
                            @endphp
                            <div class="p-5 bg-slate-50 border border-slate-200 rounded-3xl space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ $i === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-700' }}">
                                            Slot {{ $i + 1 }}
                                        </span>
                                        <h4 class="text-sm font-black text-slate-900">{{ $slotTitle }}</h4>
                                    </div>
                                    <button type="button"
                                            @click="manualMode[{{ $i }}] = !manualMode[{{ $i }}]"
                                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer">
                                        <span x-text="manualMode[{{ $i }}] ? '← Select from Faculty Directory' : '+ Supervisor not listed? Enter Name & Email'"></span>
                                    </button>
                                </div>

                                {{-- Existing Faculty Directory Picker --}}
                                <div x-show="!manualMode[{{ $i }}]" class="space-y-2.5">
                                    <input type="text"
                                           x-model="slotSearch[{{ $i }}]"
                                           placeholder="Filter faculty by name, email, or rank..."
                                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                    <select name="supervisor_ids[{{ $i }}]"
                                            :disabled="manualMode[{{ $i }}]"
                                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">-- Choose {{ $i === 0 ? 'Lead Supervisor' : 'Co-Supervisor' }} --</option>
                                        <template x-for="sup in filteredSupervisors({{ $i }})" :key="sup.id">
                                            <option :value="sup.id"
                                                    :selected="'{{ old("supervisor_ids.{$i}") }}' === sup.id"
                                                    x-text="`${sup.name} (${sup.rank || 'Faculty'})${sup.email ? ' — ' + sup.email : ''}`"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Manual Unlisted Supervisor Entry --}}
                                <div x-show="manualMode[{{ $i }}]" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Full Name *</label>
                                        <input type="text"
                                               name="new_supervisors[{{ $i }}][name]"
                                               value="{{ old("new_supervisors.{$i}.name") }}"
                                               :disabled="!manualMode[{{ $i }}]"
                                               placeholder="e.g. Prof. Ada Obi"
                                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Email Address *</label>
                                        <input type="email"
                                               name="new_supervisors[{{ $i }}][email]"
                                               value="{{ old("new_supervisors.{$i}.email") }}"
                                               :disabled="!manualMode[{{ $i }}]"
                                               placeholder="supervisor@noun.edu.ng"
                                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Academic Rank</label>
                                        <select name="new_supervisors[{{ $i }}][rank]"
                                                :disabled="!manualMode[{{ $i }}]"
                                                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800">
                                            <option value="Professor" {{ old("new_supervisors.{$i}.rank", $i === 0 ? 'Professor' : '') === 'Professor' ? 'selected' : '' }}>Professor</option>
                                            <option value="Associate Professor" {{ old("new_supervisors.{$i}.rank") === 'Associate Professor' ? 'selected' : '' }}>Associate Professor</option>
                                            <option value="Senior Lecturer" {{ old("new_supervisors.{$i}.rank", $i > 0 ? 'Senior Lecturer' : '') === 'Senior Lecturer' ? 'selected' : '' }}>Senior Lecturer</option>
                                            <option value="Lecturer I" {{ old("new_supervisors.{$i}.rank") === 'Lecturer I' ? 'selected' : '' }}>Lecturer I</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endfor

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                            <p class="text-xs font-semibold text-slate-500">
                                Recording your existing supervisors notifies your Program Coordinator and advances your thesis to Proposal Defence.
                            </p>
                            <button type="submit"
                                    :disabled="submittingSelfSups"
                                    class="inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-indigo-600/30 active:scale-95 disabled:opacity-50 cursor-pointer shrink-0">
                                <span x-text="submittingSelfSups ? 'Saving Supervisors...' : 'Save Supervisors & Continue'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- OPTION 2 PANEL: Upload Tentative Proposal & Request Supervisors --}}
                <div x-show="studentPath === 'request'" class="space-y-6">
                    @if(!$hasUploaded)
                        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-10 relative overflow-hidden">
                            <div class="flex items-start gap-5 mb-6">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 shadow-sm">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-black uppercase tracking-wider mb-2 border border-emerald-100">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                        <span>Proposal &amp; Supervisor Request</span>
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                        Upload Tentative Proposal &amp; Request Supervisors
                                    </h3>
                                    <p class="text-sm font-medium text-slate-600 mt-1.5 leading-relaxed max-w-3xl">
                                        Upload your tentative research proposal (PDF). Once submitted, your Program Coordinator will receive an <strong>immediate email</strong> showing your name (<strong class="text-slate-900">{{ $studentName }}</strong>) and matric number (<strong class="text-slate-900">{{ $matricNumber }}</strong>) to assign fitting supervisors.
                                    </p>
                                </div>
                            </div>

                            <form action="{{ route('milestones.store', $milestone) }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  @submit="
                                    const fileInput = $event.target.querySelector('input[type=file]');
                                    if (!fileInput || !fileInput.files[0]) {
                                        $event.preventDefault();
                                        fileError = 'Please select your tentative proposal PDF.';
                                        return;
                                    }
                                    if (fileInput.files[0].size > 30 * 1024 * 1024) {
                                        $event.preventDefault();
                                        fileError = 'The selected file exceeds the 30MB limit.';
                                        return;
                                    }
                                    uploading = true;
                                  "
                                  class="space-y-5">
                                @csrf

                                <div class="relative w-full">
                                    <input type="file"
                                           name="file"
                                           accept=".pdf"
                                           required
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                           @change="
                                               const f = $event.target.files[0];
                                               document.getElementById('proposal-file-name-{{ $milestone->id }}').textContent = f ? f.name : 'Click or drop to select your tentative proposal (PDF Only)';
                                               if (f && f.size > 30 * 1024 * 1024) {
                                                   fileError = 'The file exceeds 30MB.';
                                                   $event.target.value = '';
                                               } else {
                                                   fileError = '';
                                               }
                                           "/>
                                    <div class="w-full flex flex-col items-center justify-center gap-4 px-6 py-10 bg-slate-50 border-2 border-slate-200 border-dashed rounded-3xl hover:border-emerald-400 hover:bg-emerald-50/40 transition-all cursor-pointer group">
                                        <div class="w-14 h-14 rounded-2xl bg-white text-emerald-600 flex items-center justify-center shadow-lg shadow-slate-200/50 group-hover:scale-110 transition-transform">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>
                                        <div class="text-center">
                                            <span id="proposal-file-name-{{ $milestone->id }}" class="text-sm font-bold text-slate-800 group-hover:text-emerald-700 transition-colors">
                                                Click or drop to select your tentative research proposal (PDF Only)
                                            </span>
                                            <p class="text-xs font-medium text-slate-400 mt-1">PDF document up to 30MB</p>
                                        </div>
                                    </div>
                                </div>

                                <span x-show="fileError" x-text="fileError" class="text-xs font-bold text-rose-600 block" style="display: none;"></span>

                                <div>
                                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-1.5">Research Interest / Note for Coordinator (Optional)</label>
                                    <textarea name="description"
                                              rows="2"
                                              placeholder="Briefly describe your tentative research focus or any note for your Program Coordinator..."
                                              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span>Sends immediate email &amp; portal alert to your Program Coordinator</span>
                                    </div>
                                    <button type="submit"
                                            :disabled="uploading"
                                            class="inline-flex items-center justify-center gap-2.5 px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-emerald-600/30 active:scale-95 disabled:opacity-50 cursor-pointer shrink-0">
                                        <span x-text="uploading ? 'Uploading & Emailing Coordinator...' : 'Upload Proposal & Request Supervisors'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        {{-- State 1B: Student HAS uploaded tentative proposal; show request confirmation & resend option --}}
                        <div class="bg-gradient-to-br from-emerald-50 via-teal-50/50 to-white rounded-[2.5rem] border border-emerald-200 shadow-xl shadow-slate-200/40 p-8 sm:p-10 relative overflow-hidden">
                            <div class="relative flex items-start gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/25">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="space-y-2 max-w-3xl">
                                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider border border-emerald-200">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                        <span>{{ $hasRequestedSupervisors ? 'Supervisor Request Emailed to Coordinator' : 'Tentative Proposal Uploaded' }}</span>
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
                                        @if($hasRequestedSupervisors)
                                            Your Program Coordinator has been notified of your supervisor request ({{ $studentName }} — {{ $matricNumber }}).
                                        @else
                                            Your tentative proposal is uploaded. Click below to send your formal supervisor request email to your Program Coordinator.
                                        @endif
                                    </h3>
                                    @if(!empty($supervisorRequest['notified_coordinator_names']))
                                        <p class="text-xs sm:text-sm font-medium text-slate-600">
                                            Notified Coordinator(s): <strong class="text-slate-800">{{ implode(', ', $supervisorRequest['notified_coordinator_names']) }}</strong>
                                            @if(!empty($supervisorRequest['requested_at']))
                                                &bull; Sent {{ \Carbon\Carbon::parse($supervisorRequest['requested_at'])->diffForHumans() }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Uploaded Proposal Document Card --}}
                        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                    <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                                    Uploaded Tentative Proposal
                                </h3>
                                <span class="text-xs font-bold text-slate-400">
                                    Version 0{{ $latestProposal->version }} • {{ $latestProposal->created_at->format('M d, Y') }}
                                </span>
                            </div>

                            <div class="p-5 bg-slate-50 border border-slate-100 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-slate-200 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center shrink-0 shadow-sm font-black text-xs">
                                        PDF
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900">
                                            {{ $latestProposal->file_meta['original_name'] ?? 'Tentative_Research_Proposal.pdf' }}
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Submitted {{ $latestProposal->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click.prevent="$dispatch('open-document-preview', { url: '{{ $proposalUrl }}', title: 'Tentative Proposal - {{ addslashes($studentName) }}', type: 'pdf' })"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors border border-indigo-200 cursor-pointer">
                                        <span>Preview Document</span>
                                    </button>
                                    <a href="{{ $proposalUrl }}" download class="p-2 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl transition-colors" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Explicit Request / Re-send Request Form --}}
                        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-6 sm:p-8 space-y-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                                {{ $hasRequestedSupervisors ? 'Update Proposal or Follow Up with Coordinator' : 'Send Supervisor Request Email Now' }}
                            </h3>
                            <form action="{{ route('milestones.supervisors.request', $milestone) }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  @submit="requestingSups = true"
                                  class="space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Replace Tentative Proposal PDF (Optional)</label>
                                        <input type="file" name="file" accept=".pdf" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Message / Research Area Note (Optional)</label>
                                        <input type="text" name="description" value="{{ old('description') }}" placeholder="Note for Program Coordinator..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700" />
                                    </div>
                                </div>
                                <button type="submit"
                                        :disabled="requestingSups"
                                        class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md cursor-pointer disabled:opacity-50">
                                    <span x-text="requestingSups ? 'Sending Email...' : '{{ $hasRequestedSupervisors ? 'Re-send Request Email to Coordinator' : 'Request Supervisors (Email Coordinator Immediately)' }}'"></span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 2. PROGRAM COORDINATOR SIDE VIEW                                          --}}
    {{-- ========================================================================= --}}
    @elseif(auth()->user()->hasRole('Program Coordinator'))
        @if(!$hasUploaded && !$hasSupervisors)
            {{-- State 2A: Student has NOT uploaded proposal or inserted supervisors yet --}}
            <div class="bg-amber-50/70 border border-amber-200 rounded-[2.5rem] p-8 sm:p-10 shadow-xl shadow-slate-200/40 relative overflow-hidden animate-in-up">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/25">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-black uppercase tracking-wider mb-2 border border-amber-200">
                                <span>Awaiting Candidate Submission</span>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                                Student yet to upload tentative proposal
                            </h2>
                            <p class="text-sm font-medium text-slate-700 mt-1.5 leading-relaxed max-w-2xl">
                                Candidate <strong class="text-slate-900">{{ $studentName }}</strong> has not uploaded their tentative research proposal yet. You can send a direct reminder to their inbox to prompt submission.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <button type="button" 
                                @click.prevent.stop="showStudentMessageModal = true; messageRecipient = '{{ addslashes($studentName) }}'"
                                class="inline-flex items-center gap-2 px-6 py-3.5 bg-amber-600 hover:bg-amber-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-amber-600/30 active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span>Send Message to Student</span>
                        </button>

                        <button type="button" 
                                @click="showAssignModal = true"
                                class="inline-flex items-center gap-2 px-5 py-3.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 rounded-2xl text-xs font-bold uppercase tracking-wider transition-colors shadow-sm cursor-pointer">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span>Assign Supervisors</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- State 2B: Student HAS uploaded proposal --}}
            <div class="space-y-6 animate-in-up">
                {{-- Document Overview & Click-to-Preview Card --}}
                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-8 sm:p-10">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider mb-2 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                <span>{{ $hasUploaded ? 'Proposal Available for Review' : ($hasSelfInserted ? 'Supervisors Recorded by Candidate' : 'Supervisors Assigned') }}</span>
                            </div>
                            @if(($supervisorRequest['status'] ?? null) === 'pending')
                                <span class="ml-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-black uppercase tracking-wider border border-indigo-200">
                                    Supervisor Requested ({{ $studentName }} &bull; {{ $matricNumber }})
                                </span>
                            @endif
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                                {{ $hasUploaded ? 'Candidate Tentative Research Proposal' : 'Candidate Supervisory Panel' }}
                            </h2>
                            <p class="text-sm font-medium text-slate-500 mt-1">
                                {{ $hasUploaded ? 'Click on the document below to preview the upload and match supervisors to this candidate\'s research focus.' : 'Candidate recorded their existing supervisors on the portal. You may review or modify assignments below.' }}
                            </p>
                        </div>

                        {{-- Assign Supervisors Trigger Button --}}
                        <div class="shrink-0">
                            <button type="button" 
                                    @click="showAssignModal = true"
                                    class="inline-flex items-center gap-2 px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-indigo-600/30 active:scale-95 cursor-pointer">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>{{ $hasSupervisors ? 'Modify / Reassign Supervisors' : 'Assign Supervisors' }}</span>
                            </button>
                        </div>
                    </div>

                    @if($hasUploaded && $latestProposal)
                    {{-- Clickable Document Card --}}
                    <div class="mt-6">
                        <div @click.prevent="$dispatch('open-document-preview', { 
                                 url: '{{ $proposalUrl }}', 
                                 title: 'Tentative Proposal - {{ addslashes($studentName) }}', 
                                 type: 'pdf' 
                             })"
                             class="group p-6 bg-slate-50 hover:bg-indigo-50/50 border-2 border-slate-200 hover:border-indigo-300 rounded-3xl transition-all cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-white text-rose-600 border border-slate-200 flex items-center justify-center font-black text-sm shadow-md group-hover:scale-105 transition-transform shrink-0">
                                    PDF
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $latestProposal->file_meta['original_name'] ?? 'Tentative_Research_Proposal.pdf' }}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-100 text-indigo-800">
                                            Click to Preview
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Uploaded by {{ $studentName }} on {{ $latestProposal->created_at->format('M d, Y • h:i A') }} • Version 0{{ $latestProposal->version }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider group-hover:bg-indigo-700 transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Preview Document</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Current Supervisory Committee Allocation --}}
                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                                Allocated Supervisors
                            </h3>
                            <span class="text-xs font-bold {{ $hasSupervisors ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $hasSupervisors ? $assignments->count() . ' of ' . $requiredSupervisorCount . ' Assigned' : 'Awaiting Assignment' }}
                            </span>
                        </div>

                        @if($hasSupervisors)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($assignments as $assignment)
                                    @php $sup = $assignment->supervisor; @endphp
                                    <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-indigo-600 font-black text-sm shadow-sm">
                                            {{ substr($sup->user?->name ?? 'S', 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ $assignment->role === 'primary' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                                                    {{ $assignment->role === 'primary' ? 'Lead' : 'Co-Supervisor' }}
                                                </span>
                                                <span class="text-[10px] font-bold text-slate-400">{{ $sup->rank }}</span>
                                            </div>
                                            <h4 class="text-sm font-bold text-slate-900 truncate mt-0.5">{{ $sup->user?->name }}</h4>
                                            <p class="text-xs text-slate-500 truncate">{{ $sup->department ?? $programName }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-6 bg-amber-50/60 border border-amber-200 rounded-2xl flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="text-xs font-semibold text-amber-900">
                                        Supervisors have not yet been assigned to this candidate. Click <strong>Assign Supervisors</strong> to select or randomize.
                                    </span>
                                </div>
                                <button type="button" @click="showAssignModal = true" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shrink-0 transition-colors shadow-sm">
                                    Assign Now
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 3. ADMIN SIDE VIEW                                                        --}}
    {{-- ========================================================================= --}}
    @elseif(auth()->user()->hasRole('Admin'))
        <div class="space-y-6 animate-in-up">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Panel 1: Uploaded Proposal by Student --}}
                <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                                Candidate Proposal Document
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $hasUploaded ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $hasUploaded ? 'Uploaded' : 'Pending' }}
                            </span>
                        </div>

                        @if($hasUploaded)
                            <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center font-black text-xs text-rose-600 shadow-sm shrink-0">
                                    PDF
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-slate-900 truncate">
                                        {{ $latestProposal->file_meta['original_name'] ?? 'Tentative_Research_Proposal.pdf' }}
                                    </h4>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Version 0{{ $latestProposal->version }} • {{ $latestProposal->created_at->format('M d, Y • h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="p-6 bg-amber-50/60 border border-amber-200 rounded-2xl text-center mb-4">
                                <svg class="w-8 h-8 text-amber-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <p class="text-xs font-bold text-amber-900">Student is yet to upload tentative proposal</p>
                                <p class="text-[11px] text-amber-700 mt-1">Awaiting candidate proposal submission.</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                        @if($hasUploaded)
                            <button type="button" 
                                    @click.prevent="$dispatch('open-document-preview', { 
                                        url: '{{ $proposalUrl }}', 
                                        title: 'Tentative Proposal - {{ addslashes($studentName) }}', 
                                        type: 'pdf' 
                                    })"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md active:scale-95 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Preview Uploaded Proposal</span>
                            </button>
                        @else
                            <button type="button" 
                                    @click.prevent.stop="showStudentMessageModal = true; messageRecipient = '{{ addslashes($studentName) }}'"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors shadow-sm cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span>Message Student</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Panel 2: Supervisors Allocated by Programme Coordinator --}}
                <div @if(auth()->user()->hasRole('Admin')) x-data="{ editingAdminAssignId: null }" @endif class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                                Coordinator Supervisor Allocation
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $hasSupervisors ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $hasSupervisors ? 'Allocated (' . $assignments->count() . ')' : 'Pending Allocation' }}
                            </span>
                        </div>

                        @if($hasSupervisors)
                            <div class="space-y-3 mb-4">
                                @foreach($assignments as $assignment)
                                    @php
                                        $sup = $assignment->supervisor;
                                        $isPrimaryRole = in_array($assignment->role, ['primary', 'main'], true);
                                    @endphp
                                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-indigo-600 font-bold text-xs shadow-sm shrink-0">
                                                    {{ substr($sup->user?->name ?? 'S', 0, 1) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-bold text-slate-900 truncate">{{ $sup->user?->name }}</span>
                                                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded {{ $isPrimaryRole ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                                                            {{ $isPrimaryRole ? 'Lead' : 'Co-Sup' }}
                                                        </span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 truncate">{{ $sup->rank }} • {{ $sup->department ?? $programName }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span class="text-[10px] font-bold text-slate-400 mr-1">
                                                    Load: {{ $sup->current_load }}/{{ $sup->max_students }}
                                                </span>
                                                @if(auth()->user()->hasRole('Admin') && $student)
                                                    <button type="button"
                                                            @click="editingAdminAssignId = editingAdminAssignId === '{{ $assignment->id }}' ? null : '{{ $assignment->id }}'"
                                                            class="px-2 py-1 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 hover:text-indigo-700 text-[10px] font-bold text-slate-700 cursor-pointer">
                                                        Change
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.students.supervisors.destroy', [$student, $assignment]) }}" onsubmit="return confirm('Remove {{ addslashes($sup->user?->name ?? 'this supervisor') }} from this student?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-2 py-1 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-[10px] font-bold text-rose-600 cursor-pointer">
                                                            Remove
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        @if(auth()->user()->hasRole('Admin') && $student)
                                            <form x-show="editingAdminAssignId === '{{ $assignment->id }}'"
                                                  x-cloak
                                                  method="POST"
                                                  action="{{ route('admin.students.supervisors.update', [$student, $assignment]) }}"
                                                  class="pt-2.5 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="supervisor_profile_id" required class="sm:col-span-6 w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-800">
                                                    @foreach($availableSupervisors as $availSup)
                                                        <option value="{{ $availSup->id }}" @selected($availSup->id === $assignment->supervisor_profile_id)>
                                                            {{ $availSup->rank }} {{ $availSup->user?->name }} ({{ $availSup->current_load }}/{{ $availSup->max_students }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select name="role" required class="sm:col-span-4 w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-800">
                                                    <option value="primary" @selected($isPrimaryRole)>Lead Supervisor</option>
                                                    <option value="secondary" @selected(!$isPrimaryRole)>Co-Supervisor</option>
                                                </select>
                                                <button type="submit" class="sm:col-span-2 w-full px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider cursor-pointer">
                                                    Save
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-2xl text-center mb-4">
                                <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <p class="text-xs font-bold text-slate-700">Awaiting supervisor allocation by Program Coordinator</p>
                                <p class="text-[11px] text-slate-500 mt-1">Required: {{ $requiredSupervisorCount }} supervisors (Lead must be Professor)</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 pt-4 border-t border-slate-100">
                        <button type="button" 
                                @click="showAssignModal = true"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span>{{ $hasSupervisors ? 'Manage / Override Supervisors' : 'Assign Supervisors' }}</span>
                        </button>
                        @if(auth()->user()->hasRole('Admin') && $hasSupervisors && $student)
                            <form method="POST" action="{{ route('admin.students.supervisors.clear', $student) }}" onsubmit="return confirm('Remove ALL assigned supervisors from this student?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-3 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                                    Remove All
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 4. GENERAL / OTHER ROLES FALLBACK                                         --}}
    {{-- ========================================================================= --}}
    @else
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-xl shadow-slate-200/40 p-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Supervisors Assigned Overview</h3>
                    <p class="text-xs text-slate-500">Candidate: {{ $studentName }} • Program: {{ $programName }}</p>
                </div>
                @if($hasUploaded)
                    <button type="button" 
                            @click.prevent="$dispatch('open-document-preview', { 
                                url: '{{ $proposalUrl }}', 
                                title: 'Tentative Proposal - {{ addslashes($studentName) }}', 
                                type: 'pdf' 
                            })"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors border border-indigo-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>Preview Proposal</span>
                    </button>
                @endif
            </div>

            @if($hasSupervisors)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($assignments as $assignment)
                        @php $sup = $assignment->supervisor; @endphp
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-indigo-600 font-bold text-xs shadow-sm">
                                {{ substr($sup->user?->name ?? 'S', 0, 1) }}
                            </div>
                            <div>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded {{ $assignment->role === 'primary' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $assignment->role === 'primary' ? 'Lead' : 'Co-Supervisor' }}
                                </span>
                                <h4 class="text-sm font-bold text-slate-900">{{ $sup->user?->name }}</h4>
                                <p class="text-xs text-slate-500">{{ $sup->rank }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs font-semibold text-slate-500">Supervisors have not yet been assigned by the Program Coordinator.</p>
            @endif
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL: ASSIGN SUPERVISORS (COORDINATOR / ADMIN)                            --}}
    {{-- ========================================================================= --}}
    @if(auth()->user()->hasAnyRole(['Program Coordinator', 'Admin']))
        <template x-teleport="body">
            <div x-show="showAssignModal" 
                 class="fixed z-50 inset-0 overflow-y-auto" 
                 style="display: none;" 
                 x-cloak>
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showAssignModal" 
                         @click="showAssignModal = false"
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0" 
                         x-transition:enter-end="opacity-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100" 
                         x-transition:leave-end="opacity-0" 
                         class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm cursor-pointer" 
                         aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showAssignModal" 
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100 relative z-10">
                        
                        <form action="{{ route('theses.assign_supervisor', $milestone->thesis_project_id) }}" 
                              method="POST"
                              @submit="
                                  if (!selectedLead || !selectedSecond || (isPhD && !selectedThird)) {
                                      $event.preventDefault();
                                      alert('Please select all required supervisors.');
                                      return;
                                  }
                                  const ids = [selectedLead, selectedSecond];
                                  if (isPhD) ids.push(selectedThird);
                                  const unique = new Set(ids);
                                  if (unique.size !== ids.length) {
                                      $event.preventDefault();
                                      alert('Each supervisor in the panel must be unique. You cannot assign the same supervisor multiple times.');
                                      return;
                                  }
                              ">
                            @csrf
                            
                            {{-- Hidden inputs with selected supervisor IDs --}}
                            <input type="hidden" name="supervisors[]" :value="selectedLead">
                            <input type="hidden" name="supervisors[]" :value="selectedSecond">
                            <template x-if="isPhD">
                                <input type="hidden" name="supervisors[]" :value="selectedThird">
                            </template>

                            {{-- Modal Header --}}
                            <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 sm:p-8 text-white relative">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/10 text-indigo-300 border border-white/10 mb-2">
                                            Institutional Panel Assignment
                                        </span>
                                        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                            Assign Supervisory Committee
                                        </h3>
                                        <p class="text-xs text-slate-300 mt-1">
                                            Candidate: <strong class="text-white">{{ $studentName }}</strong> &bull; Program: <strong class="text-white">{{ $programName }}</strong> ({{ $levelName ?: 'Postgraduate' }})
                                        </p>
                                    </div>
                                    <button type="button" @click="showAssignModal = false" class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 sm:p-8 space-y-6">
                                {{-- Randomize Alert Banner --}}
                                <div x-show="randomizeAlert" 
                                     x-transition 
                                     class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-3">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="randomizeAlert"></span>
                                </div>

                                {{-- Institutional Rules Callout with Randomize Button --}}
                                <div class="p-5 bg-indigo-50/70 border border-indigo-100 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                            <span class="text-xs font-black uppercase tracking-wider text-indigo-950">
                                                {{ $isPhD ? 'PhD Protocol: 3 Members' : 'MSc Protocol: 2 Members' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-indigo-800 leading-relaxed">
                                            Lead Supervisor (Slot 1) must hold the rank of <strong>Professor</strong>.
                                        </p>
                                    </div>

                                    {{-- The RANDOMIZE Button --}}
                                    <button type="button" 
                                            @click="randomize()"
                                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-md hover:shadow-lg transition-all active:scale-95 shrink-0 cursor-pointer">
                                        <svg class="w-4 h-4 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                        <span>Randomize</span>
                                    </button>
                                </div>

                                {{-- Selection Dropdowns --}}
                                <div class="space-y-4">
                                    {{-- Slot 1: Lead Supervisor (Professor) --}}
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>1. Lead Supervisor ({{ auth()->user()->hasRole('Admin') ? 'Admin Override Allowed' : 'Rank: Professor Required' }})</span>
                                            <span class="text-[10px] text-indigo-600 font-bold">Primary Chair</span>
                                        </label>
                                        <select x-model="selectedLead" 
                                                required 
                                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            <option value="">-- Select {{ auth()->user()->hasRole('Admin') ? 'Lead Supervisor' : 'Professor for Lead Supervisor' }} --</option>
                                            <template x-for="sup in supervisorsList" :key="sup.id">
                                                <option :value="sup.id" 
                                                        @if(!auth()->user()->hasRole('Admin')) :disabled="!sup.is_professor && sup.id !== selectedLead" @endif
                                                        x-text="(sup.is_professor ? '⭐ Professor ' : sup.rank + ' ') + sup.name + ' • Load: ' + sup.current_load + '/' + sup.max_students + (!sup.is_professor ? ' (Not a Professor)' : '')">
                                                </option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Slot 2: Co-Supervisor 1 --}}
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                            <span>2. Co-Supervisor</span>
                                            <span class="text-[10px] text-slate-400 font-bold">Secondary</span>
                                        </label>
                                        <select x-model="selectedSecond" 
                                                required 
                                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            <option value="">-- Select Co-Supervisor --</option>
                                            <template x-for="sup in supervisorsList" :key="sup.id">
                                                <option :value="sup.id" 
                                                        :disabled="sup.id === selectedLead || (isPhD && sup.id === selectedThird)"
                                                        x-text="sup.rank + ' ' + sup.name + ' • Load: ' + sup.current_load + '/' + sup.max_students">
                                                </option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Slot 3: Co-Supervisor 2 (PhD Only) --}}
                                    <template x-if="isPhD">
                                        <div>
                                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                                <span>3. Second Co-Supervisor (PhD Requirement)</span>
                                                <span class="text-[10px] text-indigo-600 font-bold">Tertiary</span>
                                            </label>
                                            <select x-model="selectedThird" 
                                                    required 
                                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                                <option value="">-- Select Second Co-Supervisor --</option>
                                                <template x-for="sup in supervisorsList" :key="sup.id">
                                                    <option :value="sup.id" 
                                                            :disabled="sup.id === selectedLead || sup.id === selectedSecond"
                                                            x-text="sup.rank + ' ' + sup.name + ' • Load: ' + sup.current_load + '/' + sup.max_students">
                                                    </option>
                                                </template>
                                            </select>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div class="bg-slate-50 px-5 sm:px-8 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
                                <p class="text-xs text-slate-500 text-center sm:text-left">
                                    Assignments will notify the student and committee members immediately.
                                </p>
                                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
                                    <button type="button" 
                                            @click="showAssignModal = false" 
                                            class="w-full sm:w-auto px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-600 hover:bg-slate-100 transition-colors shadow-sm text-center">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-indigo-600/30 active:scale-95 cursor-pointer text-center">
                                        Authorize Assignment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODAL: SEND MESSAGE TO CANDIDATE (COORDINATOR / ADMIN)                    --}}
    {{-- ========================================================================= --}}
    <template x-teleport="body">
        <div x-show="showStudentMessageModal" 
             class="fixed z-50 inset-0 overflow-y-auto" 
             style="display: none;" 
             x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showStudentMessageModal" 
                     @click="showStudentMessageModal = false"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm cursor-pointer" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showStudentMessageModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-2xl sm:rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-100 relative z-10">
                     
                    <form action="{{ route('messages.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="thesis_project_id" value="{{ $milestone->thesis_project_id }}">
                        <input type="hidden" name="student_milestone_id" value="{{ $milestone->id }}">
                        
                        <div class="bg-white px-6 pt-8 pb-6 sm:p-8">
                            <div class="sm:flex sm:items-start gap-5">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-amber-50 sm:mx-0 shadow-md border border-amber-100 text-amber-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <div class="mt-4 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                                        Send Message to Candidate
                                    </h3>
                                    <p class="mt-1 text-xs font-medium text-slate-500 mb-4">
                                        Direct inbox communication to <strong class="text-slate-800">{{ $studentName }}</strong> regarding proposal submission.
                                    </p>
                                    
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Message Content</label>
                                    <textarea name="content" rows="4" class="w-full focus:ring-amber-500 focus:border-amber-500 text-sm border-slate-200 rounded-xl p-4 bg-slate-50 transition-colors resize-none" placeholder="Dear {{ $studentName }}, please upload your tentative research proposal so we can proceed with assigning your supervisors..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-5 sm:px-8 py-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3">
                            <button type="button" @click="showStudentMessageModal = false" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-600 hover:bg-slate-100 transition-colors shadow-sm text-center">
                                Cancel
                            </button>
                            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-amber-600 rounded-xl text-xs font-black uppercase tracking-wider text-white hover:bg-amber-700 transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer text-center">
                                <span>Send Message</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>

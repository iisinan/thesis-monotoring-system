@extends('layouts.admin')

@section('content')
<div class="space-y-10 pb-10">
    @php
        $exportExcelBase = (isset($isCoordinator) && $isCoordinator)
            ? route('coordinator.milestone-templates.export-ongoing-excel')
            : route('admin.milestone-templates.export-ongoing-excel');

        $exportPdfBase = (isset($isCoordinator) && $isCoordinator)
            ? route('coordinator.milestone-templates.export-ongoing-pdf')
            : route('admin.milestone-templates.export-ongoing-pdf');
    @endphp

    <!-- Sophisticated Header with Download Ongoing Records Chooser -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6"
        x-data="{ 
            exportModalOpen: false, 
            exportDropdownOpen: false,
            selectedCohort: '',
            selectedProgram: '',
            selectedMilestone: '',
            getExcelUrl() {
                let params = new URLSearchParams();
                if (this.selectedCohort) params.append('cohort_id', this.selectedCohort);
                if (this.selectedProgram) params.append('program_id', this.selectedProgram);
                if (this.selectedMilestone) params.append('template_id', this.selectedMilestone);
                let qs = params.toString();
                return '{{ $exportExcelBase }}' + (qs ? '?' + qs : '');
            },
            getPdfUrl() {
                let params = new URLSearchParams();
                if (this.selectedCohort) params.append('cohort_id', this.selectedCohort);
                if (this.selectedProgram) params.append('program_id', this.selectedProgram);
                if (this.selectedMilestone) params.append('template_id', this.selectedMilestone);
                let qs = params.toString();
                return '{{ $exportPdfBase }}' + (qs ? '?' + qs : '');
            }
        }">
        <div>
            <div class="flex items-center gap-3 mb-2 text-acetel-600">
                <div class="p-1.5 rounded-lg bg-acetel-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                </div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Milestone Templates</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Milestones</h1>
            <p class="mt-2 text-sm font-medium text-slate-500">View the static milestones and approval requirements for student progress.</p>
        </div>

        <!-- Download Ongoing Records Action Button -->
        <div class="relative flex items-center">
            <div class="inline-flex items-center rounded-2xl shadow-sm border border-slate-200 bg-white hover:border-slate-300 transition-all overflow-visible relative">
                <button type="button" @click="exportModalOpen = true"
                    class="inline-flex items-center gap-2.5 px-4 sm:px-5 py-2.5 sm:py-3 text-xs font-black uppercase tracking-wider text-slate-800 hover:text-emerald-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download Ongoing Records</span>
                </button>
                <button type="button" @click="exportDropdownOpen = !exportDropdownOpen"
                    class="px-2.5 py-2.5 sm:py-3 border-l border-slate-200 text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition-colors"
                    title="Quick Download Options">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Quick Download Dropdown -->
                <div x-show="exportDropdownOpen" @click.outside="exportDropdownOpen = false" x-cloak x-transition
                    class="absolute right-0 top-full mt-2 w-72 bg-white border border-slate-100 rounded-2xl shadow-2xl shadow-slate-300/50 z-50 p-2 text-left">
                    <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 mb-1">
                        Instant Download (All Ongoing)
                    </div>
                    <a :href="getExcelUrl()" @click="exportDropdownOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-extrabold text-slate-800">Download Excel (.csv)</div>
                            <div class="text-[10px] font-normal text-slate-400">Complete raw table with all columns</div>
                        </div>
                    </a>
                    <a :href="getPdfUrl()" @click="exportDropdownOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-rose-50 text-slate-700 hover:text-rose-800 text-xs font-bold transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-extrabold text-slate-800">Download PDF (.pdf)</div>
                            <div class="text-[10px] font-normal text-slate-400">Formatted report by milestone stage</div>
                        </div>
                    </a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <button type="button" @click="exportDropdownOpen = false; exportModalOpen = true"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-[11px] font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                        <span>Filter & Export Options</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Chooser: Excel or PDF with Optional Filters -->
            <template x-teleport="body">
                <div x-show="exportModalOpen" x-cloak x-transition.opacity 
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @keydown.escape.window="exportModalOpen = false">
                    <div @click.outside="exportModalOpen = false" 
                        class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col border border-slate-100">
                        
                        {{-- Modal Header --}}
                        <div class="p-6 pb-4 border-b border-slate-100 flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Export Student Records</span>
                                    <h3 class="text-xl font-black text-slate-900 leading-tight mt-0.5">Download Ongoing Milestones</h3>
                                    <p class="text-xs text-slate-500 mt-1">Select your preferred export format to download records of all candidates currently active on milestones.</p>
                                </div>
                            </div>
                            <button type="button" @click="exportModalOpen = false" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Filters Section (Optional) --}}
                        <div class="p-6 bg-slate-50/70 border-b border-slate-100">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                <span>Filter Records (Optional — Default is All)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-{{ (!isset($isCoordinator) || !$isCoordinator) ? '3' : '2' }} gap-3">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">Cohort</label>
                                    <select x-model="selectedCohort" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">All Cohorts</option>
                                        @foreach($cohorts as $cohort)
                                            <option value="{{ $cohort->id }}">{{ $cohort->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(!isset($isCoordinator) || !$isCoordinator)
                                <div>
                                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">Program</label>
                                    <select x-model="selectedProgram" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">All Programs</option>
                                        @foreach($programs ?? [] as $prog)
                                            <option value="{{ $prog->id }}">{{ $prog->code }} - {{ $prog->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div>
                                    <label class="block text-[10px] font-black text-slate-600 uppercase tracking-wider mb-1">Milestone Stage</label>
                                    <select x-model="selectedMilestone" class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">All Ongoing Stages</option>
                                        @foreach($templates as $tpl)
                                            <option value="{{ $tpl->id }}">M{{ $tpl->order }}: {{ $tpl->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Format Chooser Cards --}}
                        <div class="p-6 space-y-4">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Choose Export Format
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Card 1: Excel --}}
                                <div class="p-5 rounded-2xl border-2 border-emerald-200 bg-emerald-50/30 hover:bg-emerald-50/60 transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="p-2 rounded-xl bg-emerald-100 text-emerald-800">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Excel / CSV
                                            </span>
                                        </div>
                                        <h4 class="text-base font-black text-slate-900 mb-1">Excel Spreadsheet</h4>
                                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                                            Export full tabular records containing matric numbers, names, emails, thesis titles, ongoing milestones, supervisors, defence schedules, and status.
                                        </p>
                                    </div>
                                    <a :href="getExcelUrl()" @click="exportModalOpen = false"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm shadow-emerald-200 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Download as Excel</span>
                                    </a>
                                </div>

                                {{-- Card 2: PDF --}}
                                <div class="p-5 rounded-2xl border-2 border-rose-200 bg-rose-50/30 hover:bg-rose-50/60 transition-all flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="p-2 rounded-xl bg-rose-100 text-rose-800">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">
                                                Official PDF
                                            </span>
                                        </div>
                                        <h4 class="text-base font-black text-slate-900 mb-1">PDF Master Report</h4>
                                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                                            Formatted landscape PDF with institutional ACETEL headers, summary breakdown statistics, and candidates grouped neatly by each ongoing milestone.
                                        </p>
                                    </div>
                                    <a :href="getPdfUrl()" @click="exportModalOpen = false"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm shadow-rose-200 transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Download as PDF</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                            <button type="button" @click="exportModalOpen = false"
                                class="px-5 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>


    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="w-20 px-10 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 text-center">Order</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Milestone Name</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Program</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">Requirements</th>
                        <th class="px-6 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 text-right">Schedule</th>
                    </tr>
                </thead>
                
                    @forelse($templates as $template)
                        @include('admin.milestone-templates.partials.milestone-row', ['template' => $template])
                    @empty
                    <tbody>
                    <tr>
                        <td colspan="5" class="px-10 py-20 text-center bg-slate-50/50">
                            <div class="max-w-xs mx-auto">
                                <div class="w-16 h-16 bg-white rounded-3xl border border-slate-100 flex items-center justify-center mx-auto mb-6 shadow-sm">
                                    <svg class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                </div>
                                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">No Milestones Found</h4>
                                <p class="text-xs text-slate-500 mt-2 font-medium">No milestone templates have been created yet.</p>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                    @endforelse
                </table>
        </div>
    </div>
</div>

<form id="global-jump-form" method="POST" action="" class="hidden">
    @csrf
    <input type="hidden" name="milestone_id" id="global-jump-target">
</form>

@push('scripts')
<script>
    async function jumpMilestone(studentId, targetSlug) {
        const ok = await window.confirmModal({
            title: 'Change Milestone Protocol',
            message: 'Are you sure you want to change this student\'s milestone? This will reset their progress for future milestones.',
            type: 'warning',
            confirmText: 'Change Milestone',
            cancelText: 'Cancel'
        });
        if (!ok) return;
        const form = document.getElementById('global-jump-form');
        form.action = '{{ url("admin/students") }}/' + studentId + '/set-milestone';
        document.getElementById('global-jump-target').value = targetSlug;
        form.submit();
    }

    (function () {
        if (window.__milestoneAjaxBound) return;
        window.__milestoneAjaxBound = true;
        window.__milestonePanelState = window.__milestonePanelState || {};

        function getCsrfToken(form) {
            return form.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';
        }

        function snapshotPanelState(templateId) {
            const tbody = document.getElementById('milestone-block-' + templateId);
            if (!tbody || !window.Alpine) return null;
            try {
                const scope = window.Alpine.$data(tbody);
                if (!scope) return null;
                window.__milestonePanelState[templateId] = {
                    expanded: !!scope.expanded,
                    showStudents: !!scope.showStudents,
                    search: scope.search || '',
                    cohortFilter: scope.cohortFilter || '',
                    trackFilter: scope.trackFilter || 'all',
                    degreeFilter: scope.degreeFilter || 'all',
                    scrollTop: tbody.querySelector('[data-students-scroll]')?.scrollTop || 0,
                };
                return scope;
            } catch (_) {
                return null;
            }
        }

        function resetModalSubmittingFlags(scope) {
            if (!scope) return;
            scope.isSubmittingMessage = false;
            scope.isSubmittingBulkInternal = false;
        }

        function closePanelModals(scope, templateId) {
            if (scope) {
                scope.messageModalOpen = false;
                scope.assignModalOpen = false;
                scope.bulkInternalModalOpen = false;
                scope.rescheduleDayOpen = false;
                scope.slotModalOpen = false;
                if ('editOpen' in scope) scope.editOpen = false;
            }
            document.querySelectorAll('[data-teleported-modal="' + templateId + '"]').forEach((el) => el.remove());
        }

        function replaceMilestonePanelHtml(templateId, html) {
            const oldTbody = document.getElementById('milestone-block-' + templateId);
            if (!oldTbody || !html) return;

            const tempTable = document.createElement('table');
            tempTable.innerHTML = html.trim();
            const newTbody = tempTable.querySelector('tbody');
            if (!newTbody) return;

            if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
                window.Alpine.destroyTree(oldTbody);
            }
            oldTbody.replaceWith(newTbody);
        }

        function extractErrorMessage(data, fallback) {
            if (!data) return fallback;
            if (data.errors && typeof data.errors === 'object') {
                const firstKey = Object.keys(data.errors)[0];
                if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                    return data.errors[firstKey][0];
                }
            }
            return data.message || data.error || fallback;
        }

        document.addEventListener('submit', async function (e) {
            if (e.defaultPrevented) return;
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            if (form.id === 'global-jump-form' || form.hasAttribute('data-no-ajax')) return;
            if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') return;

            const parentBlock = form.closest('[data-milestone-block]');
            const templateId = form.getAttribute('data-milestone-ajax') || parentBlock?.getAttribute('data-milestone-block');
            if (!templateId) return;

            const tbody = document.getElementById('milestone-block-' + templateId);
            if (!tbody) return;

            e.preventDefault();

            const scope = snapshotPanelState(templateId);
            const formData = new FormData(form);
            const csrfToken = getCsrfToken(form);
            const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], button:not([type])'));

            tbody.classList.add('opacity-75', 'pointer-events-none', 'transition-opacity');
            submitButtons.forEach((btn) => { btn.disabled = true; });

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                if (res.status === 419) {
                    try {
                        const refresh = await fetch('/refresh-csrf', { credentials: 'same-origin' });
                        const refreshJson = await refresh.json();
                        if (refreshJson?.token) {
                            document.querySelectorAll('input[name="_token"]').forEach((inp) => { inp.value = refreshJson.token; });
                            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', refreshJson.token);
                        }
                    } catch (_) {}
                    window.toast?.error('Session token refreshed. Please submit once more.');
                    resetModalSubmittingFlags(scope);
                    return;
                }

                let data = null;
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    // Fallback: action returned redirect/HTML without JSON envelope; re-fetch panel HTML cleanly.
                    if (!res.ok) {
                        window.toast?.error('Action failed (' + res.status + '). Please review inputs and try again.');
                        resetModalSubmittingFlags(scope);
                        return;
                    }
                    data = { ok: true, message: 'Updated successfully.' };
                }

                if (!res.ok || data?.ok === false || data?.success === false) {
                    window.toast?.error(extractErrorMessage(data, 'Could not complete action. Please check your inputs.'));
                    resetModalSubmittingFlags(scope);
                    return;
                }

                if (data?.redirect_url) {
                    if (data.message) window.toast?.success(data.message);
                    window.location.assign(data.redirect_url);
                    return;
                }

                let freshHtml = data?.html || null;
                if (!freshHtml) {
                    const panelUrl = tbody.getAttribute('data-panel-url');
                    if (panelUrl) {
                        const panelRes = await fetch(panelUrl, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (panelRes.ok) {
                            freshHtml = await panelRes.text();
                        }
                    }
                }

                if (freshHtml) {
                    closePanelModals(scope, templateId);
                    replaceMilestonePanelHtml(templateId, freshHtml);
                }

                if (data?.message) {
                    if (data.status === 'warning') {
                        window.toast?.warning(data.message);
                    } else if (data.status === 'info') {
                        window.toast?.info(data.message);
                    } else {
                        window.toast?.success(data.message);
                    }
                }
            } catch (err) {
                console.error('Milestone partial action failed:', err);
                window.toast?.error('Network error while updating milestone. Please try again.');
                resetModalSubmittingFlags(scope);
            } finally {
                const liveTbody = document.getElementById('milestone-block-' + templateId);
                if (liveTbody) {
                    liveTbody.classList.remove('opacity-75', 'pointer-events-none', 'transition-opacity');
                }
                submitButtons.forEach((btn) => { btn.disabled = false; });
            }
        }, false);
    })();
</script>
@endpush

@endsection


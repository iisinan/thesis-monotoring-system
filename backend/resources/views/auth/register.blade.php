@extends('layouts.app')

@section('title', 'Student Registration - Thesis Monitoring System')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 font-sans" x-data="registrationWizard()">
    
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 relative overflow-hidden">
        
        <!-- Main Header (Shows on Steps 2 and 3) -->
        <div x-show="step > 1" x-cloak class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900">ACETEL Postgraduate</h1>
            <h2 class="text-xl font-bold text-green-600">Registration</h2>
            
            <!-- Progress Bar -->
            <div class="mt-8 relative px-4">
                <div class="absolute top-1/2 left-8 right-8 h-1 -translate-y-1/2 bg-slate-200 z-0 rounded-full">
                    <div class="h-full bg-green-600 rounded-full transition-all duration-300" :style="'width: ' + ((step - 1) * 50) + '%'"></div>
                </div>
                
                <div class="relative z-10 flex justify-between">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="step >= 1 ? 'bg-green-600 text-white' : 'bg-white border-2 border-slate-200 text-slate-400'">1</div>
                        <span class="text-xs font-semibold mt-2" :class="step >= 1 ? 'text-green-600' : 'text-slate-400'">Profile</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="step >= 2 ? 'bg-green-600 text-white' : 'bg-white border-2 border-slate-200 text-slate-400'">2</div>
                        <span class="text-xs font-semibold mt-2" :class="step >= 2 ? 'text-green-600' : 'text-slate-400'">Milestones</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="step >= 3 ? 'bg-green-600 text-white' : 'bg-white border-2 border-slate-200 text-slate-400'">3</div>
                        <span class="text-xs font-semibold mt-2" :class="step >= 3 ? 'text-slate-400' : 'text-slate-400'">Thesis</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-medium">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="registrationForm">
            @csrf
            
            <!-- Hidden inputs to hold Alpine state for submission -->
            <input type="hidden" name="completed_milestones[]" x-model="form.completed_milestones" />
            <input type="hidden" name="supervisor_ids[]" x-model="form.principal_supervisor_id" />
            <input type="hidden" name="new_supervisors[0][name]" x-model="form.new_principal_name" />
            <input type="hidden" name="new_supervisors[0][email]" x-model="form.new_principal_email" />

            <input type="hidden" name="supervisor_ids[]" x-model="form.co_supervisor_id" />
            <input type="hidden" name="new_supervisors[1][name]" x-model="form.new_co_name" />
            <input type="hidden" name="new_supervisors[1][email]" x-model="form.new_co_email" />

            <input type="hidden" name="supervisor_ids[]" x-model="form.third_supervisor_id" />
            <input type="hidden" name="new_supervisors[2][name]" x-model="form.new_third_name" />
            <input type="hidden" name="new_supervisors[2][email]" x-model="form.new_third_email" />
            
            <!-- STEP 1: Profile -->
            <div x-show="step === 1" x-transition.opacity.duration.300ms>
                
                <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
                    <a href="{{ route('login') }}" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Cancel
                    </a>
                    <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold ml-2">1</div>
                    <h2 class="text-lg font-bold text-slate-900">Student Information</h2>
                </div>

                <div class="space-y-5">
                    
                    <!-- Error Banner -->
                    <div x-show="errorMessage" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-xl flex items-center gap-2 font-medium" x-transition>
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" name="name" required placeholder="John Doe"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <input type="email" name="email" required placeholder="john@example.com"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>

                    <!-- Password Grid -->
                    <div class="grid grid-cols-2 gap-4" x-data="{ showP: false, showC: false }">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <input :type="showP ? 'text' : 'password'" name="password" required placeholder="••••••••"
                                       class="pl-9 pr-10 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                                <button type="button" @click="showP = !showP" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <input :type="showC ? 'text' : 'password'" name="password_confirmation" required placeholder="••••••••"
                                       class="pl-9 pr-10 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                                <button type="button" @click="showC = !showC" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Matric Number -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Matriculation Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </div>
                            <input type="text" name="matric_number" required placeholder="E.G. ACE26210011"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors uppercase">
                        </div>
                    </div>

                    <!-- Place of Work -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Place of Work <span class="text-slate-400 font-normal">(Optional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <input type="text" name="place_of_work" placeholder="Where do you currently work?"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>

                    

                    <!-- Programme -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Programme</label>
                            <select name="program_id" x-model="form.program_id" required class="w-full rounded-xl border-slate-200 bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                <option value="" disabled selected>Select Programme...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="button" @click="if(validateStep1()) step = 2" 
                                class="w-full bg-[#18a04b] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-600/20 hover:bg-green-700 hover:shadow-green-700/30 transition-all flex items-center justify-center gap-2">
                            Continue to Questionnaire
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7m0 0l-7 7m7-7H6"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Milestones -->
            <div x-show="step === 2" x-cloak x-transition.opacity.duration.300ms>
                
                <!-- Main Sub-header -->
                <div x-show="!showingSubStep" class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                    <button type="button" @click="step = 1" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </button>
                    <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold ml-2">2</div>
                    <h2 class="text-lg font-bold text-slate-900">Research Progress</h2>
                </div>

                <!-- Main Questionnaire Box -->
                <div x-show="!showingSubStep" class="bg-[#f2fdf7] border border-green-100 rounded-3xl p-8 relative overflow-hidden">
                    
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-green-100 text-green-700 font-bold text-xs px-3 py-1 rounded-full whitespace-nowrap">
                            Question <span x-text="milestoneStep"></span> of 7
                        </div>
                        <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 transition-all duration-300" :style="'width: ' + ((milestoneStep / 7) * 100) + '%'"></div>
                        </div>
                    </div>

                    <h3 class="text-2xl font-bold text-slate-900 leading-tight mb-8 text-center px-4" x-html="getCurrentQuestionHtml()"></h3>

                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" @click="answerYes()" class="bg-[#18a04b] text-white font-bold py-4 rounded-2xl shadow-lg shadow-green-600/20 hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Yes, I have
                        </button>
                        <button type="button" @click="answerNo()" class="bg-white border-2 border-slate-200 text-slate-700 font-bold py-4 rounded-2xl hover:border-slate-300 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            No, I have not
                        </button>
                    </div>
                </div>
                
                <p x-show="!showingSubStep" class="text-xs text-slate-400 text-center mt-6 px-8 flex items-start justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Selecting "No" will establish this as your current stage and complete your profile.
                </p>

                <!-- Sub-step (Grade or Supervisors) -->
                <div x-show="showingSubStep" x-cloak>
                    <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                        <button type="button" @click="showingSubStep = false" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </button>
                        <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold ml-2">2</div>
                        <h2 class="text-lg font-bold text-slate-900 leading-tight" x-html="getCurrentSubTitle()"></h2>
                    </div>

                    <p class="text-sm text-slate-500 mb-6 font-medium" x-text="getCurrentSubDesc()"></p>
                    
                    <!-- Dynamic Sub-step Content -->
                    <template x-if="getCurrentSubStepType() === 'grade'">
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Course Grade</label>
                            <input type="text" placeholder="e.g. A, B+, 75%"
                                   class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4 transition-colors">
                        </div>
                    </template>
                    
                    <template x-if="getCurrentSubStepType() === 'supervisors'">
                        <div class="mb-6 space-y-4">
                            <div class="p-4 bg-white border border-slate-200 rounded-xl" x-data="{ mode: 'select', search: '', open: false, selectedName: '' }" @click.away="open = false">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 flex justify-between items-center">
                                    <span>Principal Supervisor</span>
                                    <button type="button" @click="mode = mode === 'select' ? 'manual' : 'select'; if(mode==='manual') form.principal_supervisor_id='';" class="text-blue-500 hover:underline lowercase font-medium" x-text="mode === 'select' ? 'add manually' : 'choose from list'"></button>
                                </span>
                                
                                <div x-show="mode === 'select'" class="relative">
                                    <input type="text" x-model="search" @focus="open = true" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4" placeholder="Search supervisor...">
                                    
                                    <div x-show="open" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="supervisor in serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))">
                                            <div @click="form.principal_supervisor_id = supervisor.id; search = supervisor.name; open = false; form.new_principal_name=''; form.new_principal_email='';" 
                                                 class="px-4 py-3 hover:bg-slate-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-100 last:border-0" 
                                                 x-text="supervisor.name"></div>
                                        </template>
                                        <div x-show="serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-4 py-3 text-sm text-slate-500">
                                            No supervisors found.
                                        </div>
                                    </div>
                                </div>
                                
                                <div x-show="mode === 'manual'" class="space-y-3 mt-2" x-cloak>
                                    <input type="text" x-model="form.new_principal_name" placeholder="Supervisor Full Name" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                    <input type="email" x-model="form.new_principal_email" placeholder="Supervisor Email Address" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                </div>
                            </div>
                            
                            <div class="p-4 bg-white border border-slate-200 rounded-xl" x-data="{ mode: 'select', search: '', open: false, selectedName: '' }" @click.away="open = false">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 flex justify-between items-center">
                                    <span>Co-Supervisor</span>
                                    <button type="button" @click="mode = mode === 'select' ? 'manual' : 'select'; if(mode==='manual') form.co_supervisor_id='';" class="text-blue-500 hover:underline lowercase font-medium" x-text="mode === 'select' ? 'add manually' : 'choose from list'"></button>
                                </span>
                                
                                <div x-show="mode === 'select'" class="relative">
                                    <input type="text" x-model="search" @focus="open = true" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4" placeholder="Search supervisor...">
                                    
                                    <div x-show="open" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="supervisor in serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))">
                                            <div @click="form.co_supervisor_id = supervisor.id; search = supervisor.name; open = false; form.new_co_name=''; form.new_co_email='';" 
                                                 class="px-4 py-3 hover:bg-slate-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-100 last:border-0" 
                                                 x-text="supervisor.name"></div>
                                        </template>
                                        <div x-show="serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-4 py-3 text-sm text-slate-500">
                                            No supervisors found.
                                        </div>
                                    </div>
                                </div>
                                
                                <div x-show="mode === 'manual'" class="space-y-3 mt-2" x-cloak>
                                    <input type="text" x-model="form.new_co_name" placeholder="Supervisor Full Name" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                    <input type="email" x-model="form.new_co_email" placeholder="Supervisor Email Address" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                </div>
                            </div>

                            <div class="p-4 bg-white border border-slate-200 rounded-xl" x-show="isPhd()" x-data="{ mode: 'select', search: '', open: false, selectedName: '' }" @click.away="open = false">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 flex justify-between items-center">
                                    <span>Third Supervisor</span>
                                    <button type="button" @click="mode = mode === 'select' ? 'manual' : 'select'; if(mode==='manual') form.third_supervisor_id='';" class="text-blue-500 hover:underline lowercase font-medium" x-text="mode === 'select' ? 'add manually' : 'choose from list'"></button>
                                </span>
                                
                                <div x-show="mode === 'select'" class="relative">
                                    <input type="text" x-model="search" @focus="open = true" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4" placeholder="Search supervisor...">
                                    
                                    <div x-show="open" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="supervisor in serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))">
                                            <div @click="form.third_supervisor_id = supervisor.id; search = supervisor.name; open = false; form.new_third_name=''; form.new_third_email='';" 
                                                 class="px-4 py-3 hover:bg-slate-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-100 last:border-0" 
                                                 x-text="supervisor.name"></div>
                                        </template>
                                        <div x-show="serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-4 py-3 text-sm text-slate-500">
                                            No supervisors found.
                                        </div>
                                    </div>
                                </div>
                                
                                <div x-show="mode === 'manual'" class="space-y-3 mt-2" x-cloak>
                                    <input type="text" x-model="form.new_third_name" placeholder="Supervisor Full Name" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                    <input type="email" x-model="form.new_third_email" placeholder="Supervisor Email Address" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="saveSubStep()" class="w-full bg-[#18a04b] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-600/20 hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                        <span x-text="getCurrentSubButtonText()"></span>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7m0 0l-7 7m7-7H6"/></svg>
                    </button>
                </div>

            </div>

            <!-- STEP 3: Thesis -->
            <div x-show="step === 3" x-cloak x-transition.opacity.duration.300ms>
                
                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                    <button type="button" @click="step = 2; milestoneStep = milestoneStep > 1 ? milestoneStep - 1 : 1" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </button>
                </div>

                <div class="text-center mb-8">
                    <span class="inline-block px-4 py-1.5 bg-green-100 text-green-700 text-xs font-bold uppercase tracking-widest rounded-full mb-4">Proposal Details</span>
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Research Details</h2>
                    <p class="text-sm text-slate-500 font-medium">Please provide your approved thesis title and abstract.</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Thesis Title</label>
                        <input type="text" name="thesis_title" placeholder="Enter your approved thesis title"
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4 transition-colors">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Thesis Abstract</label>
                        <textarea name="thesis_abstract" rows="5" placeholder="Paste your approved abstract here..."
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4 transition-colors"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-[#18a04b] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-600/20 hover:bg-green-700 hover:shadow-green-700/30 transition-all flex items-center justify-center gap-2">
                        Save Details & Continue
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<script>
    // Pass the PHP milestones array to JS
    const serverMilestones = @json($milestones);
    
    
    
    const serverSupervisors = @json($supervisors->map(function($s) { return ['id' => $s->id, 'name' => $s->user->name]; }));
    const serverLevels = @json($levels);
    const serverPrograms = @json($programs);
    
    function registrationWizard() {


        return {
            step: 1,
            milestoneStep: 1,
            showingSubStep: false,
            
            errorMessage: '',
            form: {
                completed_milestones: [],
                principal_supervisor_id: '',
                new_principal_name: '',
                new_principal_email: '',
                co_supervisor_id: '',
                new_co_name: '',
                new_co_email: '',
                third_supervisor_id: '',
                new_third_name: '',
                new_third_email: '',
                program_id: ''
            },

            // Hardcode the mapping since we know the 7 exact slugs/names the user just asked for
            // Seminar course, Supervisors asigned, proposal defence, progress presentation 1, Progress Presentation 2, Internal defence, and Viva 
            questions: [
                { id: serverMilestones.find(m => m.slug === 'seminar_as_a_course')?.id, text: "Have you completed your", highlight: "Seminar Course", textAfter: "?", subStep: "grade", title: "Seminar<br>Course<br>Grade", desc: "Since you have completed your Seminar Course, please provide your grade below.", btnText: "Save Grade & Continue" },
                { id: serverMilestones.find(m => m.slug === 'supervisors_assigned')?.id, text: "Has your", highlight: "Supervisory Committee", textAfter: " been assigned?", subStep: "supervisors", title: "Assign<br>Supervisors", desc: "Since your committee is assigned, please select them below.", btnText: "Save Supervisors & Continue" },
                { id: serverMilestones.find(m => m.slug === 'proposal_defence')?.id, text: "Have you completed your", highlight: "Proposal Defence", textAfter: "?" },
                { id: serverMilestones.find(m => m.slug === 'progress_presentation_1')?.id, text: "Have you completed your", highlight: "Progress Presentation 1", textAfter: "?" },
                { id: serverMilestones.find(m => m.slug === 'progress_presentation_2')?.id, text: "Have you completed your", highlight: "Progress Presentation 2", textAfter: "?" },
                { id: serverMilestones.find(m => m.slug === 'internal_defence')?.id, text: "Have you completed your", highlight: "Internal Defence", textAfter: "?" },
                { id: serverMilestones.find(m => m.slug === 'viva')?.id, text: "Have you completed your", highlight: "Viva", textAfter: "?" }
            ],

            
            isPhd() {
                if (!this.form.program_id) return false;
                const prog = serverPrograms.find(p => p.id == this.form.program_id);
                return prog ? prog.name.toLowerCase().includes('phd') : false;
            },

            validateStep1() {
                this.errorMessage = '';
                const requiredFields = ['name', 'email', 'password', 'password_confirmation', 'matric_number', 'program_id'];
                
                // Maps input names to readable labels for better error messages
                const fieldNames = {
                    'name': 'Full Name',
                    'email': 'Email Address',
                    'password': 'Password',
                    'password_confirmation': 'Confirm Password',
                    'matric_number': 'Matriculation Number',
                    'program_id': 'Programme'
                };
                
                let isValid = true;

                // Reset existing highlights
                requiredFields.forEach(field => {
                    let el = document.querySelector(`[name="${field}"]`);
                    if(el) el.classList.remove('ring-2', 'ring-red-500', 'border-red-500', 'bg-red-50');
                });

                for(let field of requiredFields) {
                    let el = document.querySelector(`[name="${field}"]`);
                    if(el && !el.value) {
                        el.classList.add('ring-2', 'ring-red-500', 'border-red-500', 'bg-red-50');
                        isValid = false;
                    }
                }
                
                if(!isValid) {
                    this.errorMessage = 'Please fill all highlighted fields in red before continuing.';
                    return false;
                }
                
                let pw = document.querySelector(`[name="password"]`);
                let pw_conf = document.querySelector(`[name="password_confirmation"]`);
                if(pw && pw_conf && pw.value !== pw_conf.value) {
                    pw_conf.classList.add('ring-2', 'ring-red-500', 'border-red-500', 'bg-red-50');
                    this.errorMessage = 'The passwords you entered do not match.';
                    return false;
                }
                
                return true;
            },

            getCurrentQuestionHtml() {
                let q = this.questions[this.milestoneStep - 1];
                return `${q.text} <span class="text-[#18a04b] underline decoration-2 underline-offset-4">${q.highlight}</span>${q.textAfter || ''}`;
            },
            
            getCurrentSubTitle() {
                return this.questions[this.milestoneStep - 1].title;
            },
            
            getCurrentSubDesc() {
                return this.questions[this.milestoneStep - 1].desc;
            },

            getCurrentSubStepType() {
                return this.questions[this.milestoneStep - 1].subStep;
            },
            
            getCurrentSubButtonText() {
                return this.questions[this.milestoneStep - 1].btnText;
            },

            answerYes() {
                let currentQ = this.questions[this.milestoneStep - 1];
                if (currentQ.subStep) {
                    this.showingSubStep = true;
                } else {
                    this.recordMilestoneAndNext(currentQ.id);
                }
            },
            
            answerNo() {
                this.step = 3;
            },
            
            saveSubStep() {
                let currentQ = this.questions[this.milestoneStep - 1];
                this.recordMilestoneAndNext(currentQ.id);
            },
            
            recordMilestoneAndNext(id) {
                if (id && !this.form.completed_milestones.includes(id)) {
                    this.form.completed_milestones.push(id);
                }
                
                this.showingSubStep = false;
                
                if (this.milestoneStep < 7) {
                    this.milestoneStep++;
                } else {
                    this.step = 3;
                }
            },
            
            toggleSupervisor(id) {
                if(this.form.supervisor_ids.includes(id)) {
                    this.form.supervisor_ids = this.form.supervisor_ids.filter(v => v !== id);
                } else {
                    this.form.supervisor_ids.push(id);
                }
            }
        }
    }
</script>
@endsection

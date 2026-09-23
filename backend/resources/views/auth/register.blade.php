@extends('layouts.app')

@section('title', 'Student Registration - Thesis Monitoring System')

@section('content')
<div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-[#0A192F]">
    {{-- Background pattern --}}
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full opacity-20 blur-[100px]" style="background: radial-gradient(circle, #22c55e 0%, transparent 70%);"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full opacity-20 blur-[100px]" style="background: radial-gradient(circle, #3b82f6 0%, transparent 70%);"></div>
    </div>

    <div class="w-full max-w-3xl z-10 px-4 py-8">
        {{-- Card --}}
        <div class="bg-white/95 backdrop-blur-md rounded-[2rem] shadow-2xl overflow-hidden border border-white/20 p-8 md:p-12 relative"
             x-data="registrationWizard()">
            
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-green-500 via-emerald-400 to-green-600"></div>

            {{-- Header --}}
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-green-50 text-green-600 mb-4 shadow-inner">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Student Registration</h1>
                <p class="text-slate-500 mt-2 font-medium">Create your account and select your current research progress.</p>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-medium">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Progress Bar --}}
                <div class="mb-10 relative">
                    <div class="flex items-center justify-between relative z-10">
                        <template x-for="(stepName, index) in steps" :key="index">
                            <div class="flex flex-col items-center flex-1 relative">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 relative z-10"
                                     :class="step > index ? 'bg-green-500 text-white shadow-lg shadow-green-500/30' : (step === index ? 'bg-white text-green-600 border-2 border-green-500 shadow-md' : 'bg-slate-100 text-slate-400 border border-slate-200')">
                                    <span x-text="index + 1" x-show="step <= index"></span>
                                    <svg x-show="step > index" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-xs mt-3 font-bold uppercase tracking-wider transition-colors duration-300"
                                      :class="step >= index ? 'text-green-700' : 'text-slate-400'"
                                      x-text="stepName"></span>
                            </div>
                        </template>
                    </div>
                    {{-- Lines --}}
                    <div class="absolute top-5 left-0 w-full h-1 bg-slate-100 -z-0 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 transition-all duration-500 ease-in-out"
                             :style="'width: ' + ((step / (steps.length - 1)) * 100) + '%'"></div>
                    </div>
                </div>

                {{-- Step 1: Account Details --}}
                <div x-show="step === 0" x-transition.opacity.duration.300ms>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label for="first_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label for="last_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Email Address</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Password</label>
                            <input type="password" name="password" id="password" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Step 2: Academic Details --}}
                <div x-show="step === 1" x-cloak x-transition.opacity.duration.300ms>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label for="matric_number" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Matriculation Number</label>
                            <input type="text" name="matric_number" id="matric_number" value="{{ old('matric_number') }}" required placeholder="e.g. NOU12345"
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label for="admission_year" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Admission Year</label>
                            <input type="number" name="admission_year" id="admission_year" value="{{ old('admission_year', date('Y')) }}" required
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label for="program_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Program</label>
                            <select name="program_id" id="program_id" required
                                    class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                                <option value="">Select a Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label for="level_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Level / Degree</label>
                            <select name="level_id" id="level_id" required
                                    class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                                <option value="">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" {{ old('level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label for="thesis_title" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Working Thesis Title (Optional)</label>
                            <input type="text" name="thesis_title" id="thesis_title" value="{{ old('thesis_title') }}" placeholder="Enter your current research title"
                                   class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Step 3: Supervisors & Progress --}}
                <div x-show="step === 2" x-cloak x-transition.opacity.duration.300ms>
                    <div class="space-y-6">
                        
                        <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5">
                            <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Assigned Supervisors
                            </h3>
                            <p class="text-xs text-blue-600/80 mb-4 font-medium">Select your assigned supervisors if you already have them.</p>
                            
                            <div class="max-h-48 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                                @foreach($supervisors as $supervisor)
                                    <label class="flex items-center p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
                                        <input type="checkbox" name="supervisor_ids[]" value="{{ $supervisor->id }}" 
                                               class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                        <span class="ml-3 text-sm font-semibold text-slate-700 group-hover:text-blue-700 transition-colors">
                                            {{ $supervisor->user->name }}
                                            @if($supervisor->specialization)
                                                <span class="text-xs font-medium text-slate-400 block">{{ $supervisor->specialization }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-green-50/50 border border-green-100 rounded-2xl p-5">
                            <h3 class="text-sm font-bold text-green-800 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Research Progress Checklist
                            </h3>
                            <p class="text-xs text-green-600/80 mb-4 font-medium">Check off the milestones you have <strong class="font-bold">already completed</strong> and passed. They will be auto-approved in your new account.</p>
                            
                            <div class="space-y-2">
                                @foreach($milestones as $milestone)
                                    <label class="flex items-start p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-green-300 hover:shadow-sm transition-all group">
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input type="checkbox" name="completed_milestones[]" value="{{ $milestone->id }}" 
                                                   class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500">
                                        </div>
                                        <div class="ml-3">
                                            <span class="text-sm font-bold text-slate-700 group-hover:text-green-700 transition-colors block">{{ $milestone->name }}</span>
                                            @if($milestone->description)
                                                <span class="text-xs font-medium text-slate-500 block mt-0.5">{{ \Illuminate\Support\Str::limit($milestone->description, 60) }}</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div class="pt-8 flex items-center justify-between">
                    <button type="button" 
                            x-show="step > 0" 
                            @click="step--" 
                            class="px-5 py-2.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 rounded-xl text-sm font-bold uppercase tracking-wider transition-colors">
                        Back
                    </button>
                    <div x-show="step === 0"></div>

                    <button type="button" 
                            x-show="step < steps.length - 1" 
                            @click="if (validateStep()) step++" 
                            class="px-6 py-2.5 bg-slate-900 text-white hover:bg-slate-800 rounded-xl text-sm font-bold uppercase tracking-wider shadow-lg shadow-slate-900/20 transition-all flex items-center gap-2">
                        Next Step
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>

                    <button type="submit" 
                            x-show="step === steps.length - 1" 
                            class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white hover:from-green-500 hover:to-emerald-500 rounded-xl text-sm font-bold uppercase tracking-wider shadow-lg shadow-green-600/30 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                        Complete Registration
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </form>

            {{-- Sign in link --}}
            <p class="mt-8 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-bold text-green-600 hover:text-green-700 transition-colors">Sign In</a>
            </p>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }
</style>

<script>
    function registrationWizard() {
        return {
            step: 0,
            steps: ['Account', 'Academic', 'Progress'],
            validateStep() {
                // Simple HTML5 validation trigger for the current step fields
                const form = document.querySelector('form');
                // We'll just let HTML5 validate when they submit, or we can check required fields manually.
                // For a robust implementation, you'd check inputs in the current x-show div.
                // For now, we allow them to click next and rely on backend / final submit validation.
                return true; 
            }
        }
    }
</script>
@endsection

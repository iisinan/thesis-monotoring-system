@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Create New User</h2>
            <p class="mt-2 text-sm text-black">Provide the details to create a new user account.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Users
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <h3 class="text-sm font-bold text-red-800">There were issues with your submission:</h3>
                    </div>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside ml-8 font-medium">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST" x-data="{ 
                role: '{{ old('role') }}',
                programs: {{ old('coordinator_programs') ? json_encode(old('coordinator_programs')) : '[]' }},
                addProgram() {
                    this.programs.push('');
                },
                removeProgram(index) {
                    this.programs.splice(index, 1);
                }
            }" x-init="if((role === 'Program Coordinator' || role === 'Internal Examiner' || role === 'External Examiner' || role === 'Supervisor') && programs.length === 0) addProgram()">
                @csrf
                <div class="grid grid-cols-6 gap-6">
                    <!-- Name -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="name" class="block text-sm font-semibold text-black mb-1">Full Name</label>
                        <input type="text" name="name" id="name" autocomplete="name" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('name') }}" placeholder="e.g. Jane Doe" required>
                        @error('name') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="email" class="block text-sm font-semibold text-black mb-1">Email Address</label>
                        <input type="email" name="email" id="email" autocomplete="email" class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" value="{{ old('email') }}" placeholder="e.g. jane@university.edu" required>
                        @error('email') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Role -->
                    <div class="col-span-6 sm:col-span-6">
                        <label for="role" class="block text-sm font-semibold text-black mb-1">System Role</label>
                        <select id="role" name="role" x-model="role" 
                            @change="
                                if((role === 'Program Coordinator' || role === 'Internal Examiner' || role === 'External Examiner' || role === 'Supervisor')) {
                                    if(programs.length === 0) addProgram();
                                } else {
                                    programs = [];
                                }
                            " 
                            class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black" required>
                            <option value="">Select an access level...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ Str::title($role) }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Rank (For Supervisors) -->
                    <div x-show="role === 'Supervisor'" x-cloak class="col-span-6 sm:col-span-6">
                        <label for="rank" class="block text-sm font-semibold text-black mb-1">Academic Rank</label>
                        <select id="rank" name="rank" class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black">
                            <option value="">Select Rank...</option>
                            <option value="Professor">Professor</option>
                            <option value="Associate Professor">Associate Professor</option>
                            <option value="Reader">Reader</option>
                            <option value="Senior Lecturer">Senior Lecturer</option>
                            <option value="Lecturer I">Lecturer I</option>
                            <option value="Lecturer II">Lecturer II</option>
                        </select>
                        @error('rank') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Program Assignment (Coordinator, Examiners & Supervisor) -->
                    <div x-show="role === 'Program Coordinator' || role === 'Internal Examiner' || role === 'External Examiner' || role === 'Supervisor'" x-cloak class="col-span-6 space-y-4 pt-6 mt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold leading-6 text-black">Program Assignment</h3>
                                <p class="text-sm text-black">Assign the programs this coordinator will manage.</p>
                            </div>
                            <button type="button" @click="addProgram()" class="inline-flex items-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-bold rounded-lg text-black bg-white hover:bg-slate-50 focus:outline-none transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                Add Program
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(prog, index) in programs" :key="index">
                                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                    <div class="flex-1">
                                        <select :name="'coordinator_programs['+index+']'" x-model="programs[index]" class="block w-full py-2 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 text-sm font-medium text-black">
                                            <option value="">Select Program...</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program->id }}">{{ $program->name }} ({{ $program->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" @click="removeProgram(index)" class="p-2 text-slate-400 hover:text-red-600 transition-colors" x-show="programs.length > 1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Student Details Section -->
                    <div x-show="role === 'Student'" x-cloak class="col-span-6 space-y-6 pt-6 mt-4 border-t border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold leading-6 text-black">Student Info</h3>
                            <p class="text-sm text-black">Provide the academic details for this student account.</p>
                        </div>

                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-3">
                                <label for="student_id_number" class="block text-sm font-semibold text-black mb-1">Matric/Student ID</label>
                                <input type="text" name="student_id_number" id="student_id_number" 
                                    class="focus:ring-acetel-500 focus:border-acetel-500 block w-full shadow-sm sm:text-sm border-slate-300 rounded-xl px-4 py-2.5 transition-colors" 
                                    value="{{ old('student_id_number') }}" placeholder="e.g. ACE2510001">
                                @error('student_id_number') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="cohort_id" class="block text-sm font-semibold text-black mb-1">Academic Cohort</label>
                                <select id="cohort_id" name="cohort_id" 
                                    class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black">
                                    <option value="">Select Cohort...</option>
                                    @foreach($cohorts as $cohort)
                                        <option value="{{ $cohort->id }}" {{ old('cohort_id') == $cohort->id ? 'selected' : '' }}>{{ $cohort->name }}</option>
                                    @endforeach
                                </select>
                                @error('cohort_id') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="program_id" class="block text-sm font-semibold text-black mb-1">Program</label>
                                <select id="program_id" name="program_id" 
                                    class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black" :required="role === 'Student'">
                                    <option value="">Select Program...</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                                    @endforeach
                                </select>
                                @error('program_id') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label for="level_id" class="block text-sm font-semibold text-black mb-1">Level</label>
                                <select id="level_id" name="level_id" 
                                    class="block w-full py-2.5 px-4 border border-slate-300 bg-white rounded-xl shadow-sm focus:outline-none focus:ring-acetel-500 focus:border-acetel-500 sm:text-sm font-medium text-black" :required="role === 'Student'">
                                    <option value="">Select Level...</option>
                                    @foreach($levels as $level)
                                        <option value="{{ $level->id }}" {{ old('level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                                    @endforeach
                                </select>
                                @error('level_id') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>



                    <!-- Security Automation Note -->
                    <div class="col-span-6">
                        <div class="p-6 bg-acetel-50 border border-acetel-100 rounded-2xl flex items-start gap-4">
                            <div class="w-10 h-10 bg-acetel-600 rounded-xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-acetel-500/20">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-black text-acetel-900 uppercase tracking-widest leading-none mb-2">Automatic Password</p>
                                <p class="text-[11px] font-medium text-acetel-700/80 leading-relaxed uppercase tracking-tighter">The system will automatically generate a secure password for this account. The login details will be sent directly to the user's email address.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="col-span-6 mt-4">
                        <div class="flex items-start bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="flex items-center h-5 mt-0.5">
                                <input id="is_active" name="is_active" type="checkbox" value="1" class="focus:ring-acetel-500 h-5 w-5 text-acetel-600 border-slate-300 rounded transition-colors" checked>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_active" class="font-bold text-black cursor-pointer">Activate Account</label>
                                <p class="text-black mt-1">If unchecked, the user will not be able to log in until an administrator manually activates their account.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Create User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection

@extends('layouts.app')

@section('content')
<div class="min-h-screen flex bg-white">

    {{-- ===== LEFT PANEL: Branding ===== --}}
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 relative overflow-hidden flex-col">
        {{-- Modern white/green transition --}}
        <div class="absolute inset-0 bg-white border-r border-green-50"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-green-50/50 to-white/0"></div>

        {{-- Sophisticated decorative elements --}}
        <div class="absolute top-0 right-0 w-[40rem] h-[40rem] bg-green-100/30 blur-[100px] rounded-full -mr-32 -mt-32 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-green-50/50 blur-[80px] rounded-full -ml-32 -mb-32 pointer-events-none"></div>


        <div class="relative z-10 flex flex-col h-full p-12 xl:p-16">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-3 group w-fit">
                <div class="w-12 h-12 rounded-2xl bg-white border border-green-100 shadow-sm flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-all">
                    <img src="{{ asset('images/acetel-logo.jpeg') }}" alt="ACETEL" class="w-8 h-8 object-contain">
                </div>
                <div>
                    <span class="block text-base font-black text-slate-900 leading-none">ACETEL TMS</span>
                    <span class="block text-[10px] font-black text-green-600 uppercase tracking-widest mt-1">Research Excellence</span>
                </div>
            </a>


            {{-- Main copy --}}
            <div class="flex-1 flex flex-col justify-center">
                <div class="mb-10">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-black uppercase tracking-wider shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Join Research Community
                    </span>
                </div>
                <h1 class="text-4xl xl:text-6xl font-black text-slate-900 leading-[1.1] mb-8 tracking-tighter">
                    Empowering <br> <span class="text-green-600">Scholarly</span> Journeys.
                </h1>
                <p class="text-slate-500 text-lg leading-relaxed max-w-sm font-medium">
                    Create your account to initiate your research trajectory and synchronize milestones with institutional standards.
                </p>


                <div class="mt-12 space-y-5">
                    <div class="flex items-center gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white border border-green-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 14l9-5-9-5-9 5 9 5zM12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        </div>
                        <p class="text-sm text-slate-600 font-black uppercase tracking-wider">Academic Roster</p>
                    </div>
                    <div class="flex items-center gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white border border-green-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <p class="text-sm text-slate-600 font-black uppercase tracking-wider">Secure Protocol</p>
                    </div>
                    <div class="flex items-center gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white border border-green-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <p class="text-sm text-slate-600 font-black uppercase tracking-wider">Instant Onboarding</p>
                    </div>
                </div>

            </div>

            <div class="border-t border-slate-100 pt-6">
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">&copy; {{ date('Y') }} ACETEL TRADEMARK RESOURCE</p>
            </div>

        </div>
    </div>

    {{-- ===== RIGHT PANEL: Form ===== --}}
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-12 xl:px-16 bg-slate-50 overflow-y-auto">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                <img src="{{ asset('images/acetel-logo.jpeg') }}" alt="ACETEL" class="w-full h-full object-cover">
            </div>
            <div>
                <span class="block text-sm font-black text-slate-900 leading-none">ACETEL TMS</span>
                <span class="block text-[9px] font-semibold text-green-600 uppercase tracking-widest mt-0.5">Thesis Monitoring</span>
            </div>
        </div>

        <div class="w-full max-w-lg" x-data="{ selectedRole: '{{ old('role', '') }}' }">
            {{-- Heading --}}
            <div class="mb-8">
                <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-2" style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;">Create your account</h2>
                <p class="text-slate-500 text-sm">Fill in the details below to get started.</p>
            </div>

            {{-- Error summary --}}
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-red-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-red-700 mb-1">Please fix the following errors</p>
                            <ul class="text-xs text-red-500 list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Name --}}
                    <div class="space-y-1.5">
                        <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Full Name</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. Ismaila Sinan"
                               class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Email Address</label>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="you@acetel.edu.ng"
                               class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password --}}
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Password</label>
                        <input type="password" name="password" id="password" required placeholder="Minimum 8 characters"
                               class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Re-enter password"
                               class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                    </div>

                    {{-- Role --}}
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="role" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Your Role</label>
                        <select name="role" id="role" required x-model="selectedRole"
                                class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                            <option value="">Select your role</option>
                            <option value="Student">Student</option>
                            <option value="Supervisor">Supervisor</option>
                        </select>
                        @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Student-specific fields --}}
                <div x-show="selectedRole === 'Student'" x-cloak x-transition class="space-y-5">
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-xs font-bold text-green-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                            Student Details
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label for="student_id_number" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Student ID</label>
                                <input type="text" name="student_id_number" id="student_id_number" value="{{ old('student_id_number') }}" placeholder="e.g. NOU12345"
                                       class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label for="program_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Program ID</label>
                                <input type="text" name="program_id" id="program_id" value="{{ old('program_id') }}" placeholder="Paste your program ID"
                                       class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Supervisor-specific fields --}}
                <div x-show="selectedRole === 'Supervisor'" x-cloak x-transition class="space-y-5">
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Supervisor Details
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label for="staff_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Staff ID</label>
                                <input type="text" name="staff_id" id="staff_id" value="{{ old('staff_id') }}" placeholder="e.g. STAFF001"
                                       class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label for="department_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Department ID</label>
                                <input type="text" name="department_id" id="department_id" value="{{ old('department_id') }}" placeholder="Paste your department ID"
                                       class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-3">
                    <button type="submit"
                        class="w-full py-3.5 px-6 flex items-center justify-center gap-2 text-sm font-bold text-white rounded-xl transition-all duration-300 group"
                        style="background: linear-gradient(135deg, #16a34a, #15803d); box-shadow: 0 4px 24px rgba(22, 163, 74, 0.4);"
                        onmouseover="this.style.boxShadow='0 8px 40px rgba(22, 163, 74, 0.6)'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.boxShadow='0 4px 24px rgba(22, 163, 74, 0.4)'; this.style.transform='translateY(0)';"
                    >
                        Create Account
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </div>
            </form>

            {{-- Sign in link --}}
            <p class="mt-8 text-center text-sm text-slate-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-bold text-green-600 hover:text-green-700 transition-colors">Sign In</a>
            </p>

            {{-- Home link --}}
            <p class="mt-4 text-center text-xs">
                <a href="/" class="font-semibold text-slate-400 hover:text-green-600 transition-colors inline-flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to home
                </a>
            </p>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection

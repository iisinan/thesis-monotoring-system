<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Academic Fortress | Thesis Monitoring System</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --academic-900: #1e293b;
            --academic-800: #334155;
            --academic-700: #475569;
            --academic-accent: #3b82f6;
            --academic-accent-dark: #2563eb;
        }

        body {
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dark .glass {
            background: rgba(26, 26, 26, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .gradient-text {
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-pattern {
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }

        .workflow-line::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #3b82f6, #94a3b8);
            transform: translateX(-50%);
            z-index: -1;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto glass rounded-2xl px-6 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-academic-900 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm0 0v7" />
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight text-slate-800 dark:text-white">Academic Fortress</span>
                    <p class="text-[9px] font-bold uppercase tracking-widest text-blue-500 leading-none">Thesis Monitoring System</p>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600 dark:text-zinc-400">
                <a href="#features" class="hover:text-academic-accent transition-colors">Features</a>
                <a href="#workflow" class="hover:text-academic-accent transition-colors">Workflow</a>
                <a href="#impact" class="hover:text-academic-accent transition-colors">Impact</a>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-5 py-2 bg-academic-900 text-white rounded-lg text-sm font-bold shadow-lg shadow-blue-500/10 hover:bg-slate-800 transition-all">Go to App</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-bold text-slate-600 dark:text-zinc-400 hover:text-academic-900 transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="px-5 py-2 bg-academic-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/10 hover:bg-slate-800 transition-all">Start Research</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 px-6 hero-pattern">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-full">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Trusted by over 500,000 Researchers</span>
                </div>
                
                <h1 class="text-5xl lg:text-7xl font-extrabold text-slate-900 dark:text-white leading-[1.1]">
                    The Gold Standard in <br>
                    <span class="gradient-text">Thesis Excellence.</span>
                </h1>
                
                <p class="text-lg text-slate-600 dark:text-zinc-400 leading-relaxed max-w-xl">
                    A high-integrity digital ecosystem designed for institutional research oversight, student progression tracking, and automated academic governance.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="{{ route('register') }}" class="flex items-center justify-center px-8 py-4 bg-academic-900 text-white rounded-2xl text-base font-bold shadow-2xl shadow-blue-500/20 hover:-translate-y-1 transition-all">
                        Initiate Portal Access
                    </a>
                    <a href="#features" class="flex items-center justify-center px-8 py-4 bg-white dark:bg-zinc-900 text-slate-700 dark:text-white border border-slate-200 dark:border-zinc-800 rounded-2xl text-base font-bold hover:bg-slate-50 transition-all">
                        Explore Methodology
                    </a>
                </div>
            </div>

            <div class="relative lg:-mr-20">
                <div class="float-animation">
                    <img src="{{ asset('assets/hero.png') }}" alt="Academic Fortress" class="rounded-[2.5rem] shadow-2xl border-8 border-white dark:border-zinc-800">
                </div>
                
                <!-- Floating Card -->
                <div class="absolute -bottom-10 -left-10 glass p-6 rounded-3xl shadow-2xl max-w-xs border border-white hidden md:block">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">✓</div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">Submission Verified</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest">Ethics Approval Batch 04</p>
                        </div>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 w-3/4"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 px-6 bg-white dark:bg-zinc-900 border-y border-slate-100 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-20 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.3em] text-blue-600">The Ecosystem</h2>
                <h3 class="text-3xl lg:text-4xl font-bold">Unifying the Academic Lifecycle</h3>
                <p class="text-slate-500 dark:text-zinc-400">A specialized environment for every role, ensuring no research milestone is left unmonitored.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Students -->
                <div class="p-8 bg-slate-50 dark:bg-zinc-800/50 rounded-[2rem] border border-slate-100 dark:border-zinc-700 hover:border-academic-accent transition-all group">
                    <div class="w-14 h-14 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm flex items-center justify-center text-academic-900 mb-6 group-hover:scale-110 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-3">For Students</h4>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">
                        Track your research progress from proposal to final viva-voce. Automated deadlines and instant supervisor feedback keep your research on pace.
                    </p>
                </div>

                <!-- Supervisors -->
                <div class="p-8 bg-slate-50 dark:bg-zinc-800/50 rounded-[2rem] border border-slate-100 dark:border-zinc-700 hover:border-academic-accent transition-all group">
                    <div class="w-14 h-14 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm flex items-center justify-center text-academic-900 mb-6 group-hover:scale-110 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-3">For Supervisors</h4>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">
                        Manage your supervisees with a centralized feedback workspace. High-fidelity review tools and submission tracking ensure quality oversight.
                    </p>
                </div>

                <!-- Directors -->
                <div class="p-8 bg-slate-50 dark:bg-zinc-800/50 rounded-[2rem] border border-slate-100 dark:border-zinc-700 hover:border-academic-accent transition-all group">
                    <div class="w-14 h-14 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm flex items-center justify-center text-academic-900 mb-6 group-hover:scale-110 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-3">For Directors</h4>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 leading-relaxed">
                        Real-time analytics on institutional academic health. Track pass rates, seminar schedules, and faculty workload at a glance.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Workflow Section -->
    <section id="workflow" class="py-24 px-6 bg-slate-50 dark:bg-zinc-950">
        <div class="max-w-7xl mx-auto">
            <div class="text-center max-w-3xl mx-auto mb-20 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-[0.3em] text-blue-600">The Journey</h2>
                <h3 class="text-3xl lg:text-4xl font-bold">Procedural Research Lifecycle</h3>
            </div>

            <div class="relative workflow-line">
                <div class="space-y-24">
                    <!-- Step 1 -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-12 relative">
                        <div class="flex-1 md:text-right">
                            <h5 class="text-2xl font-bold mb-2">Proposal Submission</h5>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm">Student uploads research title and abstract for institutional screening.</p>
                        </div>
                        <div class="w-12 h-12 bg-academic-900 rounded-full flex items-center justify-center text-white font-bold ring-8 ring-blue-50 dark:ring-blue-900/20 z-10 shrink-0">01</div>
                        <div class="flex-1 hidden md:block"></div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-12 relative">
                        <div class="flex-1 hidden md:block"></div>
                        <div class="w-12 h-12 bg-academic-accent rounded-full flex items-center justify-center text-white font-bold ring-8 ring-blue-50 dark:ring-blue-900/20 z-10 shrink-0">02</div>
                        <div class="flex-1">
                            <h5 class="text-2xl font-bold mb-2">Expert Assignment</h5>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm">Program coordinators assign optimal supervisors based on expertise and load.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-12 relative">
                        <div class="flex-1 md:text-right">
                            <h5 class="text-2xl font-bold mb-2">Review & Iteration</h5>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm">Supervisors provide detailed scholarly feedback on research drafts via the portal.</p>
                        </div>
                        <div class="w-12 h-12 bg-academic-900 rounded-full flex items-center justify-center text-white font-bold ring-8 ring-blue-50 dark:ring-blue-900/20 z-10 shrink-0">03</div>
                        <div class="flex-1 hidden md:block"></div>
                    </div>

                    <!-- Step 4 -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-12 relative">
                        <div class="flex-1 hidden md:block"></div>
                        <div class="w-12 h-12 bg-academic-accent rounded-full flex items-center justify-center text-white font-bold ring-8 ring-blue-50 dark:ring-blue-900/20 z-10 shrink-0">04</div>
                        <div class="flex-1">
                            <h5 class="text-2xl font-bold mb-2">Institutional Approval</h5>
                            <p class="text-slate-500 dark:text-zinc-400 text-sm">Directors grant final approval for seminars and viva-voce defenses.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-24 px-6">
        <div class="max-w-5xl mx-auto bg-academic-900 rounded-[3rem] p-12 lg:p-20 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500 opacity-10 blur-3xl -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500 opacity-10 blur-3xl -ml-32 -mb-32"></div>
            
            <div class="relative z-10 space-y-8">
                <h3 class="text-4xl lg:text-5xl font-bold text-white">Ready to elevate <br> your research governance?</h3>
                <p class="text-blue-100/60 max-w-xl mx-auto text-lg leading-relaxed">
                    Join hundreds of academic departments in streamlining the thesis monitoring process.
                </p>
                <div class="pt-6">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-12 py-5 bg-white text-academic-900 rounded-2xl text-lg font-bold shadow-xl hover:-translate-y-1 transition-all">
                        Create Institutional Account
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-slate-200 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-academic-900 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v7" /></svg>
                </div>
                <span class="text-lg font-bold tracking-tight text-slate-800 dark:text-white">Academic Fortress</span>
            </div>
            
            <p class="text-xs text-slate-400 font-medium">© 2026 Academic Fortress Systems. All institutional rights reserved.</p>
            
            <div class="flex items-center gap-6">
                <a href="#" class="text-xs text-slate-500 hover:text-academic-900 transition-colors">Privacy</a>
                <a href="#" class="text-xs text-slate-500 hover:text-academic-900 transition-colors">Methodology</a>
                <a href="#" class="text-xs text-slate-500 hover:text-academic-900 transition-colors">Support</a>
            </div>
        </div>
    </footer>
</body>
</html>

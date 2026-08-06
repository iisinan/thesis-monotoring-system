@props(['announcements' => null])

@if(isset($announcements) && $announcements && $announcements->count() > 0)
    <div class="mb-10 space-y-4 animate-glass-in">
        @foreach($announcements as $announcement)
            @php
                $config = match($announcement->type) {
                    'urgent' => ['color' => 'rose', 'grad' => 'bg-grad-danger', 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'],
                    'warning' => ['color' => 'amber', 'grad' => 'bg-grad-warning', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z'],
                    'success' => ['color' => 'emerald', 'grad' => 'bg-grad-success', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    default => ['color' => 'primary', 'grad' => 'bg-grad-primary', 'icon' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'],
                };
            @endphp
            <div class="group relative overflow-hidden glass dark:glass-dark rounded-[2rem] border border-{{ $config['color'] }}-500/20 shadow-xl shadow-{{ $config['color'] }}-500/5 transition-all hover:shadow-{{ $config['color'] }}-500/10">
                <div class="absolute inset-0 bg-{{ $config['color'] }}-500/[0.02] pointer-events-none"></div>
                <div class="p-6 flex items-start gap-6 relative z-10">
                    <div class="flex-shrink-0 w-12 h-12 rounded-2xl {{ $config['grad'] }} flex items-center justify-center text-white shadow-lg shadow-{{ $config['color'] }}-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            {!! $config['icon'] !!}
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-4 mb-2">
                            <h4 class="text-sm font-black text-black dark:text-white uppercase tracking-tight">{{ $announcement->title }}</h4>
                            <span class="text-[9px] font-black {{ $config['color'] == 'primary' ? 'text-primary-600' : 'text-'.$config['color'].'-600' }} dark:text-{{ $config['color'] }}-400 uppercase tracking-[0.2em] bg-{{ $config['color'] }}-500/10 px-3 py-1 rounded-full border border-{{ $config['color'] }}-500/20">
                                {{ strtoupper($announcement->type) }}
                            </span>
                        </div>
                        <p class="text-xs font-semibold text-black dark:text-black leading-relaxed mb-4">{{ $announcement->content }}</p>
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-100/50 dark:border-white/5">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-{{ $config['color'] }}-500"></span>
                                <p class="text-[10px] font-bold text-black uppercase tracking-widest">Posted {{ $announcement->created_at->diffForHumans() }}</p>
                            </div>
                            @if($announcement->creator)
                                <span class="text-gray-300 dark:text-white/10 text-[10px]">•</span>
                                <p class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest">Via {{ $announcement->creator->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

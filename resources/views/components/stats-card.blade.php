@props(['title', 'value', 'icon' => null, 'color' => 'blue', 'subtitle' => null, 'link' => null])

@php
    $colorClasses = [
        'blue' => 'bg-acetel-50 text-acetel-600 dark:bg-acetel-900/30 dark:text-acetel-400 border-acetel-100 dark:border-acetel-800/20',
        'acetel' => 'bg-acetel-50 text-acetel-600 dark:bg-acetel-900/30 dark:text-acetel-400 border-acetel-100 dark:border-acetel-800/20',
        'indigo' => 'bg-acetel-50 text-acetel-600 dark:bg-acetel-900/30 dark:text-acetel-400 border-acetel-100 dark:border-acetel-800/20',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/20',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 border-amber-100 dark:border-amber-800/20',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 border-purple-100 dark:border-purple-800/20',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 border-rose-100 dark:border-rose-800/20',
    ];
    
    $themeColor = $colorClasses[$color] ?? $colorClasses['blue'];

    $icons = [
        'users' => '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
        'academic-cap' => '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg>',
        'book-open' => '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
        'clipboard-list' => '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>',
    ];

    $renderedIcon = $icons[$icon] ?? ($icon ?: '<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>');
@endphp

@if($link)
<a href="{{ $link }}" class="glass overflow-hidden sm:rounded-[2rem] transition-all duration-500 hover:shadow-premium hover:-translate-y-1 group animate-in-up block">
@else
<div class="glass overflow-hidden sm:rounded-[2rem] transition-all duration-500 hover:shadow-premium hover:-translate-y-1 group animate-in-up">
@endif
    <div class="p-8 relative">
        <!-- Floating Decoration -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary-600/5 blur-[40px] rounded-full group-hover:bg-primary-600/10 transition-all duration-700"></div>

        <div class="flex items-center relative z-10">
            <div class="flex-shrink-0">
                <div class="rounded-2xl w-16 h-16 flex items-center justify-center border shadow-sm {{ $themeColor }} transition-all duration-500 group-hover:scale-110 group-hover:rotate-3">
                    {!! $renderedIcon !!}
                </div>
            </div>
            <div class="ml-6 w-0 flex-1">
                <dl>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">
                        {{ $title }}
                    </dt>
                    <dd class="flex items-baseline">
                        <div class="text-3xl font-bold text-gray-900 tracking-tight group-hover:text-primary-600 transition-colors duration-300">
                            {{ $value }}
                        </div>
                    </dd>
                </dl>
            </div>
        </div>
        
        @if($subtitle)
            <div class="mt-6 pt-5 border-t border-slate-100/50">
                <p class="text-sm font-medium text-gray-500">{{ $subtitle }}</p>
            </div>
        @endif
    </div>
@if($link)
</a>
@else
</div>
@endif

@props(['type' => 'default', 'label'])

@php
    $colors = [
        'default' => 'bg-primary-50 text-primary-700 border-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:border-primary-800/20',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800/20',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800/20',
        'danger'  => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800/20',
        'info'    => 'bg-acetel-50 text-acetel-700 border-acetel-100 dark:bg-acetel-900/30 dark:text-acetel-300 dark:border-acetel-800/20',
    ];
    
    $classes = $colors[$type] ?? $colors['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border shadow-premium $classes transition-all duration-500 hover:scale-105 hover:shadow-lg backdrop-blur-md"]) }}>
    {{ $label ?? $slot }}
</span>

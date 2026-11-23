@props(['title' => null, 'footer' => null])

@props(['title' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'glass rounded-2xl sm:rounded-[2rem] overflow-hidden transition-all duration-500 hover:shadow-premium hover:-translate-y-1 group animate-in-up']) }}>
    @if($title)
        <div class="px-5 sm:px-8 py-4 sm:py-6 border-b border-slate-100/50 bg-white/50 backdrop-blur-md">
            <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">{{ $title }}</h3>
        </div>
    @endif
    
    <div class="p-5 sm:p-8 relative">
        <div class="relative z-10">
            {{ $slot }}
        </div>
    </div>

    @if($footer)
        <div class="px-5 sm:px-8 py-4 sm:py-5 bg-slate-50/50 backdrop-blur-md border-t border-slate-100/50">
            {{ $footer }}
        </div>
    @endif
</div>

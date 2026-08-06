@extends('layouts.dashboard')

@section('header', 'Communication Templates')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-xl font-black text-slate-800 tracking-tight">Institutional Branding</h3>
                <p class="text-sm font-medium text-slate-500 mt-1">Manage centralized email templates for all outgoing trajectory alerts.</p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-3">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-4">
                        <th class="pb-4 pl-4 uppercase">Template Name</th>
                        <th class="pb-4 uppercase">Subject Line</th>
                        <th class="pb-4 uppercase text-center">Placeholders</th>
                        <th class="pb-4 pr-4 text-right uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="space-y-4">
                    @foreach($templates as $template)
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="py-4 pl-6 bg-white border-y border-l border-slate-100 first:rounded-l-2xl">
                            <div>
                                <p class="text-sm font-black text-slate-800">{{ $template->name }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $template->slug }}</p>
                            </div>
                        </td>
                        <td class="py-4 bg-white border-y border-slate-100">
                            <p class="text-sm font-medium text-slate-600 italic">"{{ $template->subject }}"</p>
                        </td>
                        <td class="py-4 bg-white border-y border-slate-100 text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                @foreach(explode(',', $template->placeholders) as $target)
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-full border border-emerald-100">{{ trim($target) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-4 pr-6 bg-white border-y border-r border-slate-100 last:rounded-r-2xl text-right">
                            <a href="{{ route('admin.email-templates.edit', $template) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 transition-all shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M18.364 5.636a9 9 0 0112.728 12.728M18.364 5.636l-3.536 3.536"/></svg>
                                Configure
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

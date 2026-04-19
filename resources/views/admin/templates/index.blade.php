@extends('layouts.admin')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-extrabold text-black tracking-tight">Document Templates</h1>
        <p class="mt-2 text-sm text-black font-medium">Manage master templates and downloadable resources for students and staff.</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
        <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
            Upload Template
        </a>
    </div>
</div>

<div class="mt-8 flex flex-col">
    <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-200 rounded-2xl">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-bold text-black uppercase tracking-wider">Document Title</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-bold text-black uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-bold text-black uppercase tracking-wider">Version</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-bold text-black uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-6 text-right text-xs font-bold text-black uppercase tracking-wider">
                                Management Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($templates as $template)
                        <tr class="hover:bg-slate-50 transition-colors duration-150">
                            <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-bold text-black">
                                <span class="flex items-center">
                                    <svg class="h-5 w-5 text-black mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    {{ $template->title }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-black font-medium">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-acetel-50 text-acetel-700 border border-acetel-200">
                                    {{ ucfirst($template->type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-black font-medium">v{{ $template->version }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-black">
                                @if($template->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-black border border-slate-200">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-black" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('admin.templates.download', $template) }}" class="text-black hover:text-acetel-600 transition-colors flex items-center" title="Download">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    </a>
                                     <a href="{{ route('admin.templates.edit', $template) }}" class="text-acetel-600 hover:text-acetel-900 font-semibold flex items-center ml-2">
                                         <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                         Manage
                                     </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="mt-6">
    {{ $templates->links() }}
</div>
@endsection

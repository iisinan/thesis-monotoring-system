@extends('layouts.admin')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-extrabold text-black tracking-tight">Levels</h1>
        <p class="mt-2 text-sm text-black font-medium">Manage academic levels (e.g., MSc, PhD) available in the system.</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
        <a href="{{ route('admin.levels.create') }}" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add Level
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
                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-bold text-black uppercase tracking-wider">Level Name</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-6 text-right text-xs font-bold text-black uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($levels as $level)
                        <tr class="hover:bg-slate-50 transition-colors duration-150">
                            <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-bold text-black">{{ $level->name }}</td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                     <a href="{{ route('admin.levels.edit', $level) }}" class="text-acetel-600 hover:text-acetel-900 font-semibold flex items-center">
                                         <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                         Edit
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
    {{ $levels->links() }}
</div>
@endsection

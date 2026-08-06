@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-extrabold leading-7 text-black sm:text-3xl sm:truncate">Import Users</h2>
            <p class="mt-2 text-sm text-black">Upload a CSV file to import multiple user accounts.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl shadow-sm text-sm font-bold text-black hover:bg-slate-50 focus:outline-none transition-colors">
                <svg class="w-4 h-4 mr-2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to Directory
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden rounded-2xl">
        <div class="px-4 py-5 sm:p-8">
            <div class="mb-8 bg-acetel-50 border border-acetel-100 rounded-xl p-5">
                <div class="flex">
                    <div class="flex-shrink-0 mt-0.5">
                         <svg class="h-5 w-5 text-acetel-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                             <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                         </svg>
                    </div>
                    <div class="ml-3 w-full">
                        <h3 class="text-sm font-bold text-acetel-900 uppercase tracking-widest">Automatic Ingestion</h3>
                        <div class="mt-2 text-sm text-acetel-800">
                            <p>Upload a CSV file with the following columns: <code>name</code>, <code>email</code>, <code>program</code>, and <code>matric_number</code>.</p>
                            <p class="mt-2">Accounts will be created immediately as <strong>Students</strong>. They will receive an email with their login details and an automatically generated password.</p>
                            <div class="mt-3 bg-white bg-opacity-60 rounded-xl p-4 border border-acetel-200/50 font-mono text-[10px] overflow-x-auto text-black leading-relaxed shadow-sm">
                                name,email,program,matric_number<br>
                                "Jane Doe",jane@university.edu,CS101,MAT/26/001<br>
                                "John Smith",john@university.edu,MEC/202,MAT/26/002
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 gap-6">
                    <!-- File Upload -->
                    <div>
                        <label for="csv_file" class="block text-sm font-semibold text-black mb-2">Upload CSV File</label>
                        <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-slate-300 border-dashed rounded-xl hover:bg-slate-50 transition-colors group">
                            <div class="space-y-2 text-center">
                                <svg class="mx-auto h-12 w-12 text-black group-hover:text-acetel-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex items-center justify-center text-sm text-black">
                                    <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-bold text-acetel-600 hover:text-acetel-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-acetel-500 px-1">
                                        <span>Select a file</span>
                                        <input id="csv_file" name="csv_file" type="file" class="sr-only" accept=".csv, .txt" required>
                                    </label>
                                    <span class="pl-1">or drag and drop here</span>
                                </div>
                                <p class="text-xs text-black font-medium">CSV or TXT files up to 2MB are supported.</p>
                            </div>
                        </div>
                         @error('csv_file') <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-acetel-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-acetel-700 focus:outline-none focus:ring-2 focus:ring-acetel-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

import re

with open('resources/views/auth/register.blade.php', 'r') as f:
    content = f.read()

# 1. Replace First/Last Name with Full Name
names_block = r"""
                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">First Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" name="first_name" required placeholder="John"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Last Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" name="last_name" required placeholder="Doe"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>
"""
full_name = """
                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" name="name" required placeholder="John Doe"
                                   class="pl-11 w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 transition-colors">
                        </div>
                    </div>
"""
# If the exact block fails to match due to whitespace, we'll try a regex. But let's use regex directly.
content = re.sub(r'<!-- First Name -->.*?<!-- Email Address -->', full_name.strip() + '\n\n                    <!-- Email Address -->', content, flags=re.DOTALL)

# 2. Remove Degree dropdown and merge Programme into full width
programme_block = r"""
                    <!-- Programme & Level -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Programme</label>
                            <select name="program_id" x-model="form.program_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                <option value="">Select Pro...</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Degree</label>
                            <select name="level_id" x-model="form.level_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                <option value="">Select Deg...</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
"""
new_prog = """
                    <!-- Programme -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Programme</label>
                        <select name="program_id" x-model="form.program_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                            <option value="">Select Programme...</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
"""
content = re.sub(r'<!-- Programme & Level -->.*?</div>\s*</div>', new_prog.strip(), content, flags=re.DOTALL)

# 3. Update JS state (remove level_id, add program_id)
content = content.replace("level_id: ''", "program_id: ''")

# 4. Update isPhd logic (check program name from serverPrograms instead of level)
js_add = """
    const serverLevels = @json($levels);
    const serverPrograms = @json($programs);
    
    function registrationWizard() {
"""
content = content.replace('const serverLevels = @json($levels);\n    \n    function registrationWizard() {', js_add)

isPhd_new = """
            isPhd() {
                if (!this.form.program_id) return false;
                const prog = serverPrograms.find(p => p.id == this.form.program_id);
                return prog ? prog.name.toLowerCase().includes('phd') : false;
            },
"""
content = re.sub(r'isPhd\(\) \{[\s\S]*?\},', isPhd_new.strip() + ',', content)

# 5. Update validation
content = content.replace("['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'matric_number', 'program_id', 'level_id']", "['name', 'email', 'password', 'password_confirmation', 'matric_number', 'program_id']")

with open('resources/views/auth/register.blade.php', 'w') as f:
    f.write(content)


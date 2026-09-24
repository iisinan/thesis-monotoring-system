import re

with open('resources/views/auth/register.blade.php', 'r') as f:
    content = f.read()

# 1. Update the hidden inputs at the top
hidden_old = """
            <input type="hidden" name="supervisor_ids[]" x-model="form.principal_supervisor_id" />
            <input type="hidden" name="supervisor_ids[]" x-model="form.co_supervisor_id" />
            <input type="hidden" name="supervisor_ids[]" x-model="form.third_supervisor_id" />
"""
hidden_new = """
            <input type="hidden" name="supervisor_ids[]" x-model="form.principal_supervisor_id" />
            <input type="hidden" name="new_supervisors[0][name]" x-model="form.new_principal_name" />
            <input type="hidden" name="new_supervisors[0][email]" x-model="form.new_principal_email" />

            <input type="hidden" name="supervisor_ids[]" x-model="form.co_supervisor_id" />
            <input type="hidden" name="new_supervisors[1][name]" x-model="form.new_co_name" />
            <input type="hidden" name="new_supervisors[1][email]" x-model="form.new_co_email" />

            <input type="hidden" name="supervisor_ids[]" x-model="form.third_supervisor_id" />
            <input type="hidden" name="new_supervisors[2][name]" x-model="form.new_third_name" />
            <input type="hidden" name="new_supervisors[2][email]" x-model="form.new_third_email" />
"""
content = content.replace(hidden_old.strip(), hidden_new.strip())

# 2. Add serverSupervisors to Alpine data
js_add = """
    const serverSupervisors = @json($supervisors->map(function($s) { return ['id' => $s->id, 'name' => $s->user->name]; }));
"""
content = content.replace('const serverLevels = @json($levels);', js_add.strip() + '\n    const serverLevels = @json($levels);')

# 3. Update Alpine form state
form_old = """
            form: {
                completed_milestones: [],
                principal_supervisor_id: '',
                co_supervisor_id: '',
                third_supervisor_id: '',
                program_id: ''
            },
"""
form_new = """
            form: {
                completed_milestones: [],
                principal_supervisor_id: '',
                new_principal_name: '',
                new_principal_email: '',
                co_supervisor_id: '',
                new_co_name: '',
                new_co_email: '',
                third_supervisor_id: '',
                new_third_name: '',
                new_third_email: '',
                program_id: ''
            },
"""
content = content.replace(form_old.strip(), form_new.strip())

# 4. Supervisor Template
def get_combo(title, model_id, model_name, model_email):
    return f"""
                            <div class="p-4 bg-white border border-slate-200 rounded-xl" x-data="{{ mode: 'select', search: '', open: false, selectedName: '' }}" @click.away="open = false">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 flex justify-between items-center">
                                    <span>{title}</span>
                                    <button type="button" @click="mode = mode === 'select' ? 'manual' : 'select'; if(mode==='manual') form.{model_id}='';" class="text-blue-500 hover:underline lowercase font-medium" x-text="mode === 'select' ? 'add manually' : 'choose from list'"></button>
                                </span>
                                
                                <div x-show="mode === 'select'" class="relative">
                                    <input type="text" x-model="search" @focus="open = true" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4" placeholder="Search supervisor...">
                                    
                                    <div x-show="open" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="supervisor in serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))">
                                            <div @click="form.{model_id} = supervisor.id; search = supervisor.name; open = false; form.{model_name}=''; form.{model_email}='';" 
                                                 class="px-4 py-3 hover:bg-slate-50 cursor-pointer text-sm font-medium text-slate-700 border-b border-slate-100 last:border-0" 
                                                 x-text="supervisor.name"></div>
                                        </template>
                                        <div x-show="serverSupervisors.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-4 py-3 text-sm text-slate-500">
                                            No supervisors found.
                                        </div>
                                    </div>
                                </div>
                                
                                <div x-show="mode === 'manual'" class="space-y-3 mt-2" x-cloak>
                                    <input type="text" x-model="form.{model_name}" placeholder="Supervisor Full Name" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                    <input type="email" x-model="form.{model_email}" placeholder="Supervisor Email Address" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4">
                                </div>
                            </div>
"""

principal_old = """
                            <div class="p-4 bg-white border border-slate-200 rounded-xl">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 block">Principal Supervisor</span>
                                <select x-model="form.principal_supervisor_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                    <option value="">-- Select a Supervisor --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
"""
co_old = """
                            <div class="p-4 bg-white border border-slate-200 rounded-xl">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 block">Co-Supervisor</span>
                                <select x-model="form.co_supervisor_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                    <option value="">-- Select a Supervisor --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
"""
third_old = """
                            <div class="p-4 bg-white border border-slate-200 rounded-xl" x-show="isPhd()">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 block">Third Supervisor</span>
                                <select x-model="form.third_supervisor_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                    <option value="">-- Select a Supervisor --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
"""

content = content.replace(principal_old.strip(), get_combo('Principal Supervisor', 'principal_supervisor_id', 'new_principal_name', 'new_principal_email').strip())
content = content.replace(co_old.strip(), get_combo('Co-Supervisor', 'co_supervisor_id', 'new_co_name', 'new_co_email').strip())

third_new = get_combo('Third Supervisor', 'third_supervisor_id', 'new_third_name', 'new_third_email').strip()
third_new = third_new.replace('class="p-4 bg-white border border-slate-200 rounded-xl"', 'class="p-4 bg-white border border-slate-200 rounded-xl" x-show="isPhd()"')
content = content.replace(third_old.strip(), third_new)

with open('resources/views/auth/register.blade.php', 'w') as f:
    f.write(content)

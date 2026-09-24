import re

with open('resources/views/auth/register.blade.php', 'r') as f:
    content = f.read()

# 1. Remove Admission Year input
admission_year_block = r'<!-- Admission Year -->.*?</div>\s*</div>'
content = re.sub(admission_year_block, '', content, flags=re.DOTALL)

# 2. Add x-model to Degree Select
degree_select = r'(<select name="level_id" required class="w-full rounded-xl)'
content = re.sub(degree_select, r'\1 x-model="form.level_id"', content)

# 3. Add 3rd supervisor dropdown
supervisor_template = r"""
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

third_supervisor = supervisor_template + r"""
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
content = content.replace(supervisor_template, third_supervisor)

# 4. Add third_supervisor_id to hidden inputs
hidden_input = '<input type="hidden" name="supervisor_ids[]" x-model="form.co_supervisor_id" />'
content = content.replace(hidden_input, hidden_input + '\n            <input type="hidden" name="supervisor_ids[]" x-model="form.third_supervisor_id" />')

# 5. Add Alpine.js variables and isPhd function
script_add = """
    const serverLevels = @json($levels);
    
    function registrationWizard() {
"""
content = content.replace('function registrationWizard() {', script_add)

form_state = """
            form: {
                completed_milestones: [],
                principal_supervisor_id: '',
                co_supervisor_id: '',
                third_supervisor_id: '',
                level_id: ''
            },
"""
# Replace form definition
content = re.sub(r'form: \{[\s\S]*?\},', form_state.strip() + ',', content, count=1)

isPhd_func = """
            isPhd() {
                if (!this.form.level_id) return false;
                const level = serverLevels.find(l => l.id == this.form.level_id);
                return level ? level.name.toLowerCase().includes('phd') : false;
            },
"""
content = content.replace('validateStep1() {', isPhd_func + '\n            validateStep1() {')

# 6. Remove validation for admission_year from JS validateStep1
content = content.replace("'admission_year', ", "")

with open('resources/views/auth/register.blade.php', 'w') as f:
    f.write(content)

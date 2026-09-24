import re

with open('resources/views/auth/register.blade.php', 'r') as f:
    content = f.read()

# Update Supervisor sub-step
supervisor_template = """
                    <template x-if="getCurrentSubStepType() === 'supervisors'">
                        <div class="mb-6 space-y-4">
                            <div class="p-4 bg-white border border-slate-200 rounded-xl">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 block">Principal Supervisor</span>
                                <select x-model="form.principal_supervisor_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                    <option value="">-- Select a Supervisor --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="p-4 bg-white border border-slate-200 rounded-xl">
                                <span class="text-xs font-bold text-green-700 tracking-widest uppercase mb-2 block">Co-Supervisor</span>
                                <select x-model="form.co_supervisor_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3">
                                    <option value="">-- Select a Supervisor --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </template>
"""
content = re.sub(r'<template x-if="getCurrentSubStepType\(\) === \'supervisors\'">.*?</template>', supervisor_template.strip(), content, flags=re.DOTALL)

# Update Thesis step
thesis_step = """
            <!-- STEP 3: Thesis -->
            <div x-show="step === 3" x-cloak x-transition.opacity.duration.300ms>
                
                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                    <button type="button" @click="step = 2; milestoneStep = milestoneStep > 1 ? milestoneStep - 1 : 1" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </button>
                </div>

                <div class="text-center mb-8">
                    <span class="inline-block px-4 py-1.5 bg-green-100 text-green-700 text-xs font-bold uppercase tracking-widest rounded-full mb-4">Proposal Details</span>
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Research Details</h2>
                    <p class="text-sm text-slate-500 font-medium">Please provide your approved thesis title and abstract.</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Thesis Title</label>
                        <input type="text" name="thesis_title" placeholder="Enter your approved thesis title"
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4 transition-colors">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Thesis Abstract</label>
                        <textarea name="thesis_abstract" rows="5" placeholder="Paste your approved abstract here..."
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-green-500/20 focus:border-green-500 text-sm font-medium py-3 px-4 transition-colors"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-[#18a04b] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-600/20 hover:bg-green-700 hover:shadow-green-700/30 transition-all flex items-center justify-center gap-2">
                        Save Details & Continue
                    </button>
                </div>
            </div>
"""
content = re.sub(r'<!-- STEP 3: Thesis -->.*?</form>', thesis_step.strip() + '\n\n        </form>', content, flags=re.DOTALL)

with open('resources/views/auth/register.blade.php', 'w') as f:
    f.write(content)

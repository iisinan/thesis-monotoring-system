<div x-data="{ 
        messages: [],
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id);
        },
        add(message, type = 'success') {
            const id = Date.now();
            this.messages.push({ 
                id, 
                text: message, 
                type, 
                timePassed: 0,
                duration: type === 'error' ? 8000 : 5000 
            });
            
            // Increment timer progress
            const interval = setInterval(() => {
                const msg = this.messages.find(m => m.id === id);
                if (!msg) {
                    clearInterval(interval);
                    return;
                }
                msg.timePassed += 100;
                if (msg.timePassed >= msg.duration) {
                    this.remove(id);
                    clearInterval(interval);
                }
            }, 100);
        }
    }"
    @notify.window="add($event.detail.message, $event.detail.type)"
    class="fixed top-10 right-10 z-[100] flex flex-col gap-4 pointer-events-none w-full max-w-md">
    
    <template x-for="message in messages" :key="message.id">
        <div x-transition:enter="transition cubic-bezier(0.175, 0.885, 0.32, 1.275) duration-500"
             x-transition:enter-start="translate-x-20 opacity-0 scale-95"
             x-transition:enter-end="translate-x-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-20 opacity-0"
             class="pointer-events-auto relative overflow-hidden bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-5 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border flex items-center gap-5 transition-all hover:scale-[1.02]"
             :class="{
                'border-emerald-100/50': message.type === 'success',
                'border-rose-100/50': message.type === 'error',
                'border-brand-100/50': message.type === 'info'
             }">
            
            <!-- Icon Glow -->
            <div class="absolute -left-4 w-20 h-20 blur-3xl opacity-20"
                 :class="{
                    'bg-emerald-500': message.type === 'success',
                    'bg-rose-500': message.type === 'error',
                    'bg-brand-500': message.type === 'info'
                 }"></div>

            <!-- Main Icon -->
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-lg relative z-10"
                 :class="{
                    'bg-emerald-500 text-white shadow-emerald-200': message.type === 'success',
                    'bg-rose-500 text-white shadow-rose-200': message.type === 'error',
                    'bg-brand-500 text-white shadow-brand-200': message.type === 'info'
                 }">
                <svg x-show="message.type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                <svg x-show="message.type === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <svg x-show="message.type === 'info'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>

            <div class="flex-1 min-w-0 relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]" 
                          :class="{
                             'text-emerald-600': message.type === 'success',
                             'text-rose-600': message.type === 'error',
                             'text-brand-600': message.type === 'info'
                          }" x-text="message.type"></span>
                </div>
                <p class="text-sm font-bold text-slate-800 dark:text-white leading-snug" x-text="message.text"></p>
            </div>

            <button @click="remove(message.id)" class="p-2 hover:bg-black/5 rounded-xl transition-colors relative z-10">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <!-- Progress Bar -->
            <div class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear"
                 :class="{
                    'bg-emerald-500': message.type === 'success',
                    'bg-rose-500': message.type === 'error',
                    'bg-brand-500': message.type === 'info'
                 }"
                 :style="`width: ${100 - (message.timePassed / message.duration * 100)}%`"
            ></div>
        </div>
    </template>
</div>

import './bootstrap';
import './echo';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.store('sidebar', {
    open: localStorage.getItem('sidebar-open') === 'true',
    toggle() {
        this.open = !this.open;
        localStorage.setItem('sidebar-open', this.open);
    }
});

Alpine.store('darkMode', {
    on: localStorage.getItem('dark-mode') === 'true',
    toggle() {
        this.on = !this.on;
        localStorage.setItem('dark-mode', this.on);
        if (this.on) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    init() {
        if (this.on) {
            document.documentElement.classList.add('dark');
        }
    }
});

Alpine.start();

// Real-time Inbox Listeners
const userIdMeta = document.querySelector('meta[name="user-id"]');
if (userIdMeta && window.Echo) {
    const userId = userIdMeta.getAttribute('content');
    window.Echo.private(`inbox.${userId}`)
        .listen('.MessageReceived', (e) => {
            console.log('Real-time message received:', e);
            window.dispatchEvent(new CustomEvent('notify', { 
                detail: { message: `New message from ${e.sender_name}`, type: 'info' }
            }));
            window.dispatchEvent(new CustomEvent('new-inbox-message', { detail: e }));
        })
        .listen('.MilestoneSubmitted', (e) => {
            console.log('Real-time milestone submitted:', e);
            window.dispatchEvent(new CustomEvent('notify', { 
                detail: { message: `Milestone Submitted: ${e.student_name} uploaded ${e.milestone_name}`, type: 'info' }
            }));
            window.dispatchEvent(new CustomEvent('milestone-submitted', { detail: e }));
        })
        .listen('.MilestoneApproved', (e) => {
            console.log('Milestone approved:', e);
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message: e.message, type: 'success' }
            }));
            if (window.location.pathname.includes('/milestones')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .listen('.MilestoneUpdated', (e) => {
            console.log('Milestone updated:', e);
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message: e.message, type: 'info' }
            }));
            if (window.location.pathname.includes('/milestones')) {
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .listen('.EvaluationSubmitted', (e) => {
            console.log('Real-time evaluation submitted:', e);
            window.dispatchEvent(new CustomEvent('notify', { 
                detail: { message: `Evaluation Submitted for ${e.student_name}: ${e.recommendation}`, type: 'success' }
            }));
            window.dispatchEvent(new CustomEvent('evaluation-submitted', { detail: e }));
        });
}

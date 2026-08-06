import React, { useEffect, useState } from 'react';
import api from '../api';
import { 
  BookOpen, Award, CheckCircle2, ChevronRight, 
  MapPin, Clock, FileText, Lock, Users, Terminal 
} from 'lucide-react';

export default function Welcome({ onNavigateToLogin }) {
  const [stats, setStats] = useState({
    projects_count: 0,
    students_count: 0,
    archived_count: 0,
  });
  const [announcements, setAnnouncements] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Fetch live statistics and announcements from public backend endpoint
    api.get('/public/welcome')
      .then(response => {
        if (response.data) {
          if (response.data.stats) setStats(response.data.stats);
          if (response.data.announcements) setAnnouncements(response.data.announcements);
        }
      })
      .catch(error => {
        console.warn('Failed to load live data, using fallbacks.', error);
        // Safe static mock fallbacks if backend is offline
        setStats({
          projects_count: 128,
          students_count: 94,
          archived_count: 42,
        });
        setAnnouncements([
          {
            id: 1,
            title: 'MSc/PhD Thesis Registration Deadline Extended',
            content: 'The academic board has extended the registration gate for new research cohorts until August 30th. All students must finalize their proposals with supervisors.',
            starts_at: new Date().toISOString(),
            type: 'warning'
          },
          {
            id: 2,
            title: 'Turnitin Resubmission Protocol Clarifications',
            content: 'A similarity index below 20% remains mandatory for defense clearing. Turnitin check requests must be routed via program supervisors.',
            starts_at: new Date().toISOString(),
            type: 'info'
          }
        ]);
      })
      .finally(() => {
        setLoading(false);
      });
  }, []);

  return (
    <div className="min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
      
      {/* Decorative Orbs */}
      <div className="absolute top-0 right-0 w-[45rem] h-[45rem] bg-emerald-100/30 blur-[120px] rounded-full -mr-32 -mt-32 pointer-events-none"></div>
      <div className="absolute bottom-0 left-0 w-[30rem] h-[30rem] bg-emerald-50/40 blur-[100px] rounded-full -ml-32 -mb-32 pointer-events-none"></div>

      {/* Header / Nav */}
      <header className="relative z-10 max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-2xl bg-white border border-emerald-100 shadow-sm flex items-center justify-center">
            <span className="font-black text-emerald-600 text-base">A</span>
          </div>
          <div>
            <span className="block text-sm font-black text-slate-900 leading-none">ACETEL TMS</span>
            <span className="block text-[8px] font-black text-emerald-600 uppercase tracking-widest mt-1">Research Excellence</span>
          </div>
        </div>
        <button 
          onClick={onNavigateToLogin}
          className="px-5 py-2.5 bg-slate-900 text-white hover:bg-emerald-600 text-xs font-black uppercase tracking-wider rounded-xl transition-all duration-300 shadow-sm"
        >
          Enter Portal
        </button>
      </header>

      {/* Main Container */}
      <main className="relative z-10 max-w-7xl mx-auto px-6 py-12 lg:py-20 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        
        {/* Left Copy Column */}
        <div className="lg:col-span-7 space-y-8">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-100 rounded-full text-emerald-700 text-xs font-black uppercase tracking-wider shadow-sm">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Postgraduate Research Tracking
          </div>
          
          <h1 className="text-5xl lg:text-7xl font-black text-slate-900 leading-[1.05] tracking-tight">
            Orchestrating <span className="text-emerald-600">Research</span> <br />
            Milestones & Excellence.
          </h1>
          
          <p className="text-slate-500 text-lg leading-relaxed max-w-2xl font-medium">
            Welcome to the ACETEL Thesis Monitoring System. An institutional platform designed to synchronize research progress, automate similarity audits, and facilitate communication between students, supervisors, and examiners.
          </p>

          <div className="flex flex-wrap gap-4 pt-2">
            <button 
              onClick={onNavigateToLogin}
              className="px-8 py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2 group"
            >
              Access Dashboard
              <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
            <a 
              href="#repository" 
              className="px-8 py-4 bg-white border border-slate-200 text-slate-600 hover:text-emerald-700 hover:border-emerald-200 text-xs font-black uppercase tracking-widest rounded-2xl shadow-sm transition-all"
            >
              Browse Repository
            </a>
          </div>

          {/* Stats Bar */}
          <div className="pt-8 grid grid-cols-3 gap-6 max-w-lg border-t border-slate-100">
            <div>
              <span className="block text-3xl font-black text-slate-900 tabular-nums">{stats.projects_count}+</span>
              <span className="block text-[10px] font-black text-slate-400 uppercase tracking-wider mt-1">Research Projects</span>
            </div>
            <div>
              <span className="block text-3xl font-black text-slate-900 tabular-nums">{stats.students_count}+</span>
              <span className="block text-[10px] font-black text-slate-400 uppercase tracking-wider mt-1">Active Scholars</span>
            </div>
            <div>
              <span className="block text-3xl font-black text-slate-900 tabular-nums">{stats.archived_count}+</span>
              <span className="block text-[10px] font-black text-slate-400 uppercase tracking-wider mt-1">Cleared Theses</span>
            </div>
          </div>
        </div>

        {/* Right Info Column */}
        <div className="lg:col-span-5 space-y-6">
          <div className="bg-white border border-slate-100 rounded-3xl p-8 shadow-xl shadow-slate-200/50 space-y-6">
            <div className="flex items-center justify-between border-b border-slate-50 pb-4">
              <span className="text-xs font-black text-slate-400 uppercase tracking-widest">Active Bulletins</span>
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            </div>

            <div className="space-y-4 max-h-[30rem] overflow-y-auto pr-2 custom-scrollbar">
              {announcements.map((ann, idx) => (
                <div 
                  key={ann.id || idx} 
                  className={`p-5 rounded-2xl border transition-all ${
                    ann.type === 'warning' 
                      ? 'bg-amber-50/50 border-amber-100 text-amber-900' 
                      : 'bg-emerald-50/50 border-emerald-100 text-emerald-900'
                  }`}
                >
                  <h4 className="text-sm font-bold leading-tight mb-2">{ann.title}</h4>
                  <p className="text-xs font-medium opacity-80 leading-relaxed mb-3">{ann.content}</p>
                  <div className="flex items-center gap-1.5 opacity-60 text-[10px] font-bold">
                    <Clock className="w-3.5 h-3.5" />
                    <span>{new Date(ann.starts_at).toLocaleDateString()}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

      </main>
    </div>
  );
}

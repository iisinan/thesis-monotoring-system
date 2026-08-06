import React, { useState, useEffect } from 'react';
import api from '../api';
import { 
  LogOut, User as UserIcon, BookOpen, CheckCircle, Clock, 
  AlertCircle, FileText, ChevronRight, Upload, CheckSquare, 
  Users, Layers, BarChart, Settings, Mail, RefreshCw, Presentation 
} from 'lucide-react';

export default function Dashboard({ user, onLogout }) {
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(false);
  
  // Student State
  const [milestones, setMilestones] = useState([]);
  const [thesis, setThesis] = useState(null);
  const [description, setDescription] = useState('');
  const [selectedFile, setSelectedFile] = useState(null);
  const [selectedPpt, setSelectedPpt] = useState(null);
  
  // Supervisor State
  const [students, setStudents] = useState([]);
  
  // Coordinator State
  const [stats, setStats] = useState({
    active: 45,
    pending: 12,
    completed: 18,
  });

  useEffect(() => {
    fetchData();
  }, [user]);

  const fetchData = async () => {
    setLoading(true);
    try {
      if (user.role === 'Student') {
        // Fetch student milestones
        const response = await api.get('/student/milestones');
        setMilestones(response.data.milestones || []);
        setThesis(response.data.thesis || null);
      } else if (user.role === 'Supervisor') {
        const response = await api.get('/supervisor/students');
        setStudents(response.data.students || []);
      } else if (user.role === 'Program Coordinator') {
        const response = await api.get('/coordinator/dashboard-stats');
        setStats(response.data.stats || { active: 45, pending: 12, completed: 18 });
      }
    } catch (err) {
      console.warn('Dashboard fetch failed, loading mock fallbacks.', err);
      loadMockData();
    } finally {
      setLoading(false);
    }
  };

  const loadMockData = () => {
    if (user.role === 'Student') {
      setThesis({
        title: 'Deep Learning Architectures for Academic Performance Forecasting',
        abstract: 'This thesis proposes a transformer-based sequence model to forecast postgraduate research progress, identifying potential timeline risks early.',
        status: 'proposed',
        progress_percentage: 33,
      });
      setMilestones([
        { id: '1', template: { name: 'Student Finished Coursework', order: 1, submission_type: ['file'] }, status: 'approved', approved_at: '2026-05-12' },
        { id: '2', template: { name: 'Student Assigned Supervisor', order: 2, submission_type: [] }, status: 'approved', approved_at: '2026-06-01' },
        { id: '3', template: { name: 'Student Cleared For Proposal Defence', order: 3, submission_type: ['file', 'ppt'] }, status: 'submitted', submitted_at: '2026-08-01' },
        { id: '4', template: { name: 'Student Did Proposal Defence', order: 4, submission_type: [] }, status: 'not_started' },
        { id: '5', template: { name: 'Student Cleared For Internal Defence', order: 5, submission_type: ['file', 'ppt', 'publication'] }, status: 'not_started' },
      ]);
    } else {
      setStudents([
        { id: '1', name: 'Sinan Al-Kassim', topic: 'Predictive Models in E-Learning', status: 'Proposal Approved', progress: 45 },
        { id: '2', name: 'Adebayo Johnson', topic: 'Blockchain for Academic Credential Verification', status: 'Coursework Complete', progress: 15 },
        { id: '3', name: 'Chidi Okafor', topic: 'NLP Models for Automated Feedback Generation', status: 'Internal Defence Cleared', progress: 75 }
      ]);
    }
  };

  const handleFileUpload = async (e) => {
    e.preventDefault();
    if (!selectedFile && !selectedPpt) {
      alert('Attach at least one document (Manuscript or PPT/Presentation).');
      return;
    }
    setLoading(true);
    const formData = new FormData();
    if (selectedFile) formData.append('file', selectedFile);
    if (selectedPpt) formData.append('ppt', selectedPpt);
    formData.append('description', description);

    try {
      const activeMilestone = milestones.find(m => m.status === 'not_started' || m.status === 'submitted');
      if (!activeMilestone) throw new Error('No active milestone found.');
      
      await api.post(`/milestones/${activeMilestone.id}/submit`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Milestone artifacts uploaded and submitted successfully!');
      fetchData();
    } catch (err) {
      console.error(err);
      alert('Upload failed: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
      setSelectedFile(null);
      setSelectedPpt(null);
      setDescription('');
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
      
      {/* Top Navigation */}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-40 px-6 py-4 flex items-center justify-between shadow-sm">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-black text-sm">
            T
          </div>
          <div>
            <span className="block text-sm font-black text-slate-900 leading-none">ACETEL TMS</span>
            <span className="block text-[8px] font-black text-emerald-600 uppercase tracking-widest mt-0.5">Control Panel</span>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2.5 px-3.5 py-1.5 bg-slate-100 rounded-xl">
            <UserIcon className="w-4 h-4 text-slate-500" />
            <div className="text-left">
              <span className="block text-xs font-bold text-slate-900 leading-none">{user.name}</span>
              <span className="block text-[9px] font-semibold text-emerald-600 uppercase tracking-wider mt-0.5">{user.role}</span>
            </div>
          </div>
          <button 
            onClick={onLogout}
            className="p-2.5 text-slate-400 hover:text-rose-600 bg-slate-50 hover:bg-rose-50 rounded-xl transition-all"
            title="Log Out"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </header>

      {/* Main Content Area */}
      <div className="flex-grow max-w-7xl w-full mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {/* Sidebar Tabs */}
        <aside className="lg:col-span-3 space-y-2">
          <button 
            onClick={() => setActiveTab('overview')}
            className={`w-full flex items-center gap-3 px-4.5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all text-left ${
              activeTab === 'overview' 
                ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/25' 
                : 'bg-white hover:bg-slate-100 border border-slate-200 text-slate-600'
            }`}
          >
            <Layers className="w-4 h-4" />
            Overview
          </button>
          
          {user.role === 'Student' && (
            <button 
              onClick={() => setActiveTab('milestones')}
              className={`w-full flex items-center gap-3 px-4.5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all text-left ${
                activeTab === 'milestones' 
                  ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/25' 
                  : 'bg-white hover:bg-slate-100 border border-slate-200 text-slate-600'
              }`}
            >
              <CheckSquare className="w-4 h-4" />
              Academic Milestones
            </button>
          )}

          {user.role === 'Supervisor' && (
            <button 
              onClick={() => setActiveTab('students')}
              className={`w-full flex items-center gap-3 px-4.5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all text-left ${
                activeTab === 'students' 
                  ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/25' 
                  : 'bg-white hover:bg-slate-100 border border-slate-200 text-slate-600'
              }`}
            >
              <Users className="w-4 h-4" />
              Supervised Students
            </button>
          )}

          <button 
            onClick={fetchData}
            className="w-full flex items-center gap-3 px-4.5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider bg-white hover:bg-slate-100 border border-slate-200 text-slate-500 transition-all text-left"
          >
            <RefreshCw className="w-4 h-4" />
            Synchronize Cache
          </button>
        </aside>

        {/* Tab Content Display */}
        <main className="lg:col-span-9 space-y-6">
          
          {activeTab === 'overview' && (
            <div className="space-y-6">
              
              {/* STUDENT ROLE OVERVIEW */}
              {user.role === 'Student' && thesis && (
                <div className="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm space-y-6">
                  <div className="border-b border-slate-100 pb-4">
                    <span className="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Active Research Track</span>
                    <h2 className="text-xl font-black text-slate-900 mt-1">{thesis.title}</h2>
                    <p className="text-slate-500 text-sm mt-3 leading-relaxed">{thesis.abstract}</p>
                  </div>
                  
                  {/* Progress Indicator */}
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-xs font-black text-slate-400 uppercase tracking-wider">Milestone Progress</span>
                      <span className="text-xs font-black text-emerald-600">{thesis.progress_percentage}%</span>
                    </div>
                    <div className="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-500"
                        style={{ width: `${thesis.progress_percentage}%` }}
                      ></div>
                    </div>
                  </div>
                </div>
              )}

              {/* SUPERVISOR ROLE OVERVIEW */}
              {user.role === 'Supervisor' && (
                <div className="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm space-y-6">
                  <h2 className="text-lg font-black text-slate-900">Your Supervised Candidates</h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {students.map((student) => (
                      <div key={student.id} className="p-5 border border-slate-100 rounded-2xl bg-slate-50/50 space-y-3">
                        <div className="flex items-center justify-between">
                          <h4 className="text-sm font-black text-slate-900">{student.name}</h4>
                          <span className="px-2.5 py-1 bg-emerald-50 text-[9px] font-black text-emerald-700 uppercase tracking-wider rounded-lg border border-emerald-100">{student.status}</span>
                        </div>
                        <p className="text-xs text-slate-500 font-medium">{student.topic}</p>
                        <div>
                          <div className="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div className="h-full bg-emerald-600" style={{ width: `${student.progress}%` }}></div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* COORDINATOR ROLE OVERVIEW */}
              {user.role === 'Program Coordinator' && (
                <div className="space-y-6">
                  <div className="grid grid-cols-3 gap-6">
                    <div className="bg-white p-6 border border-slate-100 rounded-3xl shadow-sm">
                      <span className="block text-2xl font-black text-slate-900">{stats.active}</span>
                      <span className="block text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Active Projects</span>
                    </div>
                    <div className="bg-white p-6 border border-slate-100 rounded-3xl shadow-sm">
                      <span className="block text-2xl font-black text-emerald-600">{stats.pending}</span>
                      <span className="block text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Pending Approvals</span>
                    </div>
                    <div className="bg-white p-6 border border-slate-100 rounded-3xl shadow-sm">
                      <span className="block text-2xl font-black text-slate-900">{stats.completed}</span>
                      <span className="block text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Completed Thesis</span>
                    </div>
                  </div>

                  <div className="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm">
                    <h3 className="text-base font-black text-slate-900 uppercase tracking-tight mb-4">Operational Alerts Pool</h3>
                    <div className="space-y-3">
                      <div className="p-4 bg-amber-50 border border-amber-100 rounded-2xl text-amber-900 flex items-start gap-3">
                        <AlertCircle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                          <p className="text-xs font-bold">Unassigned Candidates Detected</p>
                          <p className="text-[10px] opacity-80 mt-1">3 newly registered MSc AI students are waiting for supervisor matching clearances.</p>
                        </div>
                      </div>
                      <div className="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-slate-600 flex items-start gap-3">
                        <CheckCircle className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                          <p className="text-xs font-bold">Examiner Panel Setup Finalized</p>
                          <p className="text-[10px] opacity-80 mt-1">The defense panel settings for cybersecurity cohort 2025/2026 have been synchronized.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}

            </div>
          )}

          {activeTab === 'milestones' && user.role === 'Student' && (
            <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
              
              {/* Left Column: Milestone list */}
              <div className="md:col-span-7 space-y-4">
                <h3 className="text-base font-black text-slate-900 uppercase tracking-tight mb-2">Milestones Timeline</h3>
                
                <div className="space-y-3 relative before:absolute before:left-6.5 before:top-4 before:bottom-4 before:w-0.5 before:bg-slate-200">
                  {milestones.map((milestone) => (
                    <div 
                      key={milestone.id} 
                      className={`p-4.5 border rounded-2xl bg-white shadow-sm flex items-center justify-between relative z-10 ${
                        milestone.status === 'approved' 
                          ? 'border-emerald-100' 
                          : milestone.status === 'submitted'
                          ? 'border-blue-100'
                          : 'border-slate-200'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border ${
                          milestone.status === 'approved' 
                            ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                            : milestone.status === 'submitted'
                            ? 'bg-blue-50 text-blue-600 border-blue-100'
                            : 'bg-white text-slate-300 border-slate-200'
                        }`}>
                          {milestone.status === 'approved' ? <CheckCircle className="w-4 h-4" /> : <Clock className="w-4 h-4" />}
                        </div>
                        <div className="text-left">
                          <span className="block text-xs font-black text-slate-900 leading-tight">{milestone.template.name}</span>
                          <span className="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                            Sequence #{milestone.template.order} • {milestone.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Right Column: File submission manager */}
              <div className="md:col-span-5 space-y-6">
                <div className="bg-white border border-slate-100 rounded-3xl p-6.5 shadow-sm space-y-5">
                  <h4 className="text-sm font-black text-slate-900 uppercase tracking-tight">Active Artifact Portal</h4>
                  
                  <form onSubmit={handleFileUpload} className="space-y-4">
                    
                    {/* PPT Input */}
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-black text-slate-500 uppercase tracking-wider">PPT Slide Deck (PDF/PPTX)</label>
                      <div className="relative border border-dashed border-slate-200 rounded-xl hover:border-emerald-500 transition-all p-4 bg-slate-50/50 flex flex-col items-center justify-center gap-2">
                        <Presentation className="w-5 h-5 text-slate-400" />
                        <span className="text-[10px] text-slate-500 text-center font-bold">
                          {selectedPpt ? selectedPpt.name : 'Select Slides File'}
                        </span>
                        <input 
                          type="file" 
                          accept=".ppt,.pptx,.pdf"
                          onChange={(e) => setSelectedPpt(e.target.files[0])}
                          className="absolute inset-0 opacity-0 cursor-pointer w-full h-full" 
                        />
                      </div>
                    </div>

                    {/* Manuscript Input */}
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Manuscript (PDF/DOCX)</label>
                      <div className="relative border border-dashed border-slate-200 rounded-xl hover:border-emerald-500 transition-all p-4 bg-slate-50/50 flex flex-col items-center justify-center gap-2">
                        <FileText className="w-5 h-5 text-slate-400" />
                        <span className="text-[10px] text-slate-500 text-center font-bold">
                          {selectedFile ? selectedFile.name : 'Select Manuscript File'}
                        </span>
                        <input 
                          type="file" 
                          onChange={(e) => setSelectedFile(e.target.files[0])}
                          className="absolute inset-0 opacity-0 cursor-pointer w-full h-full" 
                        />
                      </div>
                    </div>

                    {/* Description */}
                    <div className="space-y-1.5">
                      <label className="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Remarks / Notes</label>
                      <textarea 
                        rows="3" 
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="Add comments for your supervisor review..."
                        className="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-semibold placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"
                      />
                    </div>

                    <button 
                      type="submit" 
                      disabled={loading}
                      className="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 shadow-sm transition-all"
                    >
                      <Upload className="w-4 h-4" />
                      Submit Milestone
                    </button>
                  </form>
                </div>
              </div>

            </div>
          )}

        </main>
      </div>
    </div>
  );
}

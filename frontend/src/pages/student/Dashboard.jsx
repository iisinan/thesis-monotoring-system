import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { getCurrentUser } from '../../api';

export default function StudentDashboard() {
  const user = getCurrentUser();
  const [data, setData]     = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/dashboard').then(r => { setData(r.data); setLoading(false); })
       .catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  const thesis     = data?.thesis;
  const milestones = data?.milestones ?? [];
  const stats      = data?.stats ?? {};

  return (
    <div>
      {/* Hero Banner */}
      <div className="hero-banner">
        <div>
          <div className="hero-tag">Welcome back</div>
          <h1 className="hero-title">{user?.name}</h1>
          <p className="hero-sub">Your research dashboard is ready. Stay on track with your thesis journey.</p>
        </div>
        <div className="hero-badge" style={{ marginTop: 16 }}>
          <span className="hero-pulse" />
          Student
        </div>
      </div>

      {/* Stats */}
      <div className="stat-grid" style={{ marginBottom: 28 }}>
        <div className="stat-card">
          <div className="stat-icon green"><svg style={{width:20,height:20}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
          <div className="stat-number">{stats.completed_milestones ?? 0}</div>
          <div className="stat-label">Milestones Completed</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon amber"><svg style={{width:20,height:20}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
          <div className="stat-number">{stats.pending_milestones ?? 0}</div>
          <div className="stat-label">Pending Milestones</div>
        </div>
        <div className="stat-card dark">
          <div className="stat-icon" style={{ background: 'rgba(255,255,255,.1)', color: '#4ade80' }}><svg style={{width:20,height:20}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div className="stat-number" style={{ color: '#fff' }}>{stats.overall_progress ?? 0}%</div>
          <div className="stat-label">Overall Progress</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon blue"><svg style={{width:20,height:20}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
          <div className="stat-number">{stats.total_milestones ?? 0}</div>
          <div className="stat-label">Total Milestones</div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* Active Thesis */}
        <div className="card">
          <div className="card-title">Active Research Project</div>
          {thesis ? (
            <>
              <h3 style={{ fontSize: 15, fontWeight: 800, color: 'var(--slate-900)', lineHeight: 1.4 }}>{thesis.title}</h3>
              <p style={{ fontSize: 12, color: 'var(--slate-500)', marginTop: 8, lineHeight: 1.6 }}>{thesis.abstract?.slice(0, 200)}{thesis.abstract?.length > 200 ? '…' : ''}</p>
              <div style={{ marginTop: 16 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 6 }}>
                  <span style={{ fontSize: 10, fontWeight: 800, textTransform: 'uppercase', letterSpacing: '.08em', color: 'var(--slate-400)' }}>Progress</span>
                  <span style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)' }}>{stats.overall_progress ?? 0}%</span>
                </div>
                <div className="progress-wrap"><div className="progress-bar" style={{ width: `${stats.overall_progress ?? 0}%` }} /></div>
              </div>
              <div style={{ marginTop: 12 }}>
                <span className={`badge ${thesis.status === 'active' ? 'green' : 'slate'}`}>{thesis.status}</span>
              </div>
            </>
          ) : (
            <div className="empty-state">
              <div className="empty-icon">📄</div>
              <div className="empty-title">No thesis registered yet</div>
              <div className="empty-desc">Contact your coordinator to get started.</div>
            </div>
          )}
        </div>

        {/* Recent Milestones */}
        <div className="card">
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
            <div className="card-title" style={{ marginBottom: 0 }}>Recent Milestones</div>
            <Link to="/app/milestones" style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)', textDecoration: 'none' }}>View All →</Link>
          </div>
          <div className="milestone-list">
            {milestones.slice(0, 5).map(m => (
              <div key={m.id} className="milestone-item">
                <div className={`m-dot ${m.status === 'approved' ? 'done' : m.status === 'submitted' ? 'submitted' : 'pending'}`}>
                  {m.status === 'approved'
                    ? <svg style={{width:14,height:14}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3"><polyline points="20 6 9 17 4 12"/></svg>
                    : <svg style={{width:14,height:14}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  }
                </div>
                <div className="m-info">
                  <div className="m-name">{m.template?.name ?? `Milestone ${m.template?.order}`}</div>
                  <div className="m-meta">#{m.template?.order} · {m.status?.replace(/_/g,' ')}</div>
                </div>
              </div>
            ))}
            {milestones.length === 0 && (
              <div className="empty-state"><div className="empty-title">No milestones loaded</div></div>
            )}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="card" style={{ marginTop: 20 }}>
        <div className="card-title">Quick Actions</div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12 }}>
          <Link to="/app/milestones" style={{ background: 'var(--green-50)', border: '1px solid var(--green-100)', borderRadius: 12, padding: '16px', textDecoration: 'none', display: 'block' }}>
            <div style={{ fontSize: 20, marginBottom: 8 }}>📋</div>
            <div style={{ fontWeight: 800, fontSize: 13, color: 'var(--slate-900)' }}>Submit Milestone</div>
            <div style={{ fontSize: 11, color: 'var(--slate-500)', marginTop: 3 }}>Upload documents and PPT</div>
          </Link>
          <Link to="/app/inbox" style={{ background: 'var(--slate-50)', border: '1px solid var(--slate-100)', borderRadius: 12, padding: '16px', textDecoration: 'none', display: 'block' }}>
            <div style={{ fontSize: 20, marginBottom: 8 }}>📬</div>
            <div style={{ fontWeight: 800, fontSize: 13, color: 'var(--slate-900)' }}>Open Inbox</div>
            <div style={{ fontSize: 11, color: 'var(--slate-500)', marginTop: 3 }}>Messages from supervisor</div>
          </Link>
          <Link to="/app/repository" style={{ background: 'var(--slate-50)', border: '1px solid var(--slate-100)', borderRadius: 12, padding: '16px', textDecoration: 'none', display: 'block' }}>
            <div style={{ fontSize: 20, marginBottom: 8 }}>📚</div>
            <div style={{ fontWeight: 800, fontSize: 13, color: 'var(--slate-900)' }}>Repository</div>
            <div style={{ fontSize: 11, color: 'var(--slate-500)', marginTop: 3 }}>Browse published research</div>
          </Link>
        </div>
      </div>
    </div>
  );
}

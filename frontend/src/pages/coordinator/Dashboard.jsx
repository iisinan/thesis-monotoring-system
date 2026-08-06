import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api, { getCurrentUser } from '../../api';

export default function CoordinatorDashboard() {
  const user = getCurrentUser();
  const [data, setData]     = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/dashboard').then(r => { setData(r.data); setLoading(false); })
       .catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  const stats    = data?.stats ?? {};
  const students = data?.students ?? [];

  return (
    <div>
      <div className="hero-banner">
        <div>
          <div className="hero-tag">Programme Overview</div>
          <h1 className="hero-title">Program Status</h1>
          <p className="hero-sub">Comprehensive overview of students and supervision trajectory across your assigned programmes.</p>
        </div>
        <div className="hero-badge" style={{ marginTop: 16 }}>
          <span className="hero-pulse" />
          Coordinator
        </div>
      </div>

      {/* Stats */}
      <div className="stat-grid" style={{ marginBottom: 24 }}>
        <div className="stat-card">
          <div className="stat-icon green">👥</div>
          <div className="stat-number">{stats.total_students ?? 0}</div>
          <div className="stat-label">Students Registered</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon blue">🧑‍🏫</div>
          <div className="stat-number">{stats.total_supervisors ?? 0}</div>
          <div className="stat-label">Active Supervisors</div>
        </div>
        <div className="stat-card dark">
          <div className="stat-icon" style={{ background: 'rgba(255,255,255,.1)', color: '#4ade80' }}>📈</div>
          <div className="stat-number" style={{ color: '#fff' }}>{stats.active_theses ?? 0}</div>
          <div className="stat-label">Active Theses</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon amber">⏳</div>
          <div className="stat-number">{stats.pending_reviews ?? 0}</div>
          <div className="stat-label">Pending Reviews</div>
        </div>
      </div>

      {/* Quick Actions */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12, marginBottom: 24 }}>
        {[
          { to: '/app/coordinator/students', icon: '👥', label: 'Manage Students',    desc: 'View and manage programme students' },
          { to: '/app/inbox',                icon: '📬', label: 'Inbox',              desc: 'Read messages and notifications' },
          { to: '/app/repository',           icon: '📚', label: 'Thesis Repository',  desc: 'Browse published research' },
        ].map(a => (
          <Link key={a.to} to={a.to} style={{ background: '#fff', border: '1px solid var(--slate-100)', borderRadius: 16, padding: '18px', textDecoration: 'none', display: 'block', transition: 'all .15s' }}>
            <div style={{ fontSize: 22, marginBottom: 10 }}>{a.icon}</div>
            <div style={{ fontWeight: 800, fontSize: 13, color: 'var(--slate-900)' }}>{a.label}</div>
            <div style={{ fontSize: 11, color: 'var(--slate-500)', marginTop: 4 }}>{a.desc}</div>
          </Link>
        ))}
      </div>

      {/* Recent students */}
      <div className="card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
          <div className="card-title" style={{ marginBottom: 0 }}>Recent Students</div>
          <Link to="/app/coordinator/students" style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)', textDecoration: 'none' }}>View All →</Link>
        </div>
        {students.length === 0 ? (
          <div className="empty-state"><div className="empty-title">No students found</div></div>
        ) : (
          <div className="tbl-wrap">
            <table>
              <thead><tr><th>Name</th><th>Programme</th><th>Level</th><th>Status</th><th>Progress</th></tr></thead>
              <tbody>
                {students.slice(0, 10).map(s => (
                  <tr key={s.id}>
                    <td>
                      <div style={{ fontWeight: 700 }}>{s.user?.name}</div>
                      <div style={{ fontSize: 11, color: 'var(--slate-400)' }}>{s.user?.email}</div>
                    </td>
                    <td style={{ fontSize: 12 }}>{s.program?.code ?? '—'}</td>
                    <td style={{ fontSize: 12 }}>{s.level?.name ?? '—'}</td>
                    <td><span className={`badge ${s.enrollment_status === 'active' ? 'green' : 'slate'}`}>{s.enrollment_status ?? '—'}</span></td>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <div className="progress-wrap" style={{ flex: 1, minWidth: 80 }}>
                          <div className="progress-bar" style={{ width: `${s.thesis?.progress_percentage ?? 0}%` }} />
                        </div>
                        <span style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)' }}>{s.thesis?.progress_percentage ?? 0}%</span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

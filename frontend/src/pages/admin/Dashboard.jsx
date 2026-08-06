import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api, { getCurrentUser } from '../../api';

export default function AdminDashboard() {
  const user = getCurrentUser();
  const [data, setData]     = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/admin/stats').then(r => { setData(r.data); setLoading(false); })
       .catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  const stats       = data ?? {};
  const recent_logs = data?.recent_logs ?? [];
  const projects    = data?.projects ?? [];

  return (
    <div>
      {/* Header */}
      <div style={{ background: '#fff', border: '1px solid var(--green-100)', borderRadius: 28, padding: '36px 40px', marginBottom: 28, position: 'relative', overflow: 'hidden' }}>
        <div style={{ position: 'absolute', top: -80, right: -80, width: 260, height: 260, background: 'var(--green-50)', borderRadius: '50%', filter: 'blur(60px)' }} />
        <div style={{ position: 'relative', zIndex: 1 }}>
          <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'var(--green-50)', border: '1px solid var(--green-200)', borderRadius: 30, padding: '5px 14px', marginBottom: 14 }}>
            <span style={{ width: 7, height: 7, background: 'var(--green-500)', borderRadius: '50%', animation: 'pulse 2s infinite', display: 'inline-block' }} />
            <span style={{ fontSize: 10, fontWeight: 800, textTransform: 'uppercase', letterSpacing: '.15em', color: 'var(--green-700)' }}>Institutional Governance Core</span>
          </div>
          <h1 style={{ fontSize: 36, fontWeight: 900, color: 'var(--slate-900)' }}>System <span style={{ color: 'var(--green-600)' }}>Administration</span></h1>
          <p style={{ color: 'var(--slate-500)', fontSize: 14, marginTop: 8, maxWidth: 500 }}>Comprehensive administrative oversight for the ACETEL digital ecosystem.</p>
          <div style={{ marginTop: 20, display: 'flex', gap: 10 }}>
            <Link to="/app/admin/users"  className="btn btn-primary">User Portfolio →</Link>
            <Link to="/app/admin/theses" className="btn btn-outline">Thesis Registry</Link>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="stat-grid" style={{ marginBottom: 24 }}>
        <div className="stat-card dark">
          <div className="stat-icon" style={{ background: 'rgba(255,255,255,.1)', color: '#4ade80', marginBottom: 14 }}>👥</div>
          <div className="stat-number" style={{ color: '#fff' }}>{stats.total_users ?? 0}</div>
          <div className="stat-label">Total Users</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon green" style={{ marginBottom: 14 }}>📚</div>
          <div className="stat-number">{stats.total_theses ?? 0}</div>
          <div className="stat-label">Total Theses</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon blue" style={{ marginBottom: 14 }}>🎓</div>
          <div className="stat-number">{stats.active_students ?? 0}</div>
          <div className="stat-label">Active Students</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon amber" style={{ marginBottom: 14 }}>🏛️</div>
          <div className="stat-number">{stats.program_count ?? 0}</div>
          <div className="stat-label">Programmes</div>
        </div>
        <div className="stat-card">
          <div className="stat-icon" style={{ background: '#f0f9ff', color: '#0284c7', marginBottom: 14 }}>🧑‍💼</div>
          <div className="stat-number">{stats.staff_count ?? 0}</div>
          <div className="stat-label">Staff Members</div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr', gap: 24 }}>
        {/* Recent Theses */}
        <div className="card">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
            <div className="card-title" style={{ marginBottom: 0 }}>Recent Thesis Projects</div>
            <Link to="/app/admin/theses" style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)', textDecoration: 'none' }}>View All →</Link>
          </div>
          <div className="tbl-wrap">
            <table>
              <thead><tr><th>Student</th><th>Thesis Title</th><th>Programme</th><th>Status</th></tr></thead>
              <tbody>
                {projects.map(p => (
                  <tr key={p.id}>
                    <td style={{ fontWeight: 700 }}>{p.student?.user?.name ?? '—'}</td>
                    <td style={{ fontSize: 12, maxWidth: 200 }}>{p.title?.slice(0, 50) ?? '—'}</td>
                    <td style={{ fontSize: 12 }}>{p.student?.program?.code ?? '—'}</td>
                    <td><span className={`badge ${p.status === 'active' ? 'green' : p.status === 'completed' ? 'blue' : 'slate'}`}>{p.status}</span></td>
                  </tr>
                ))}
                {projects.length === 0 && <tr><td colSpan={4} style={{ textAlign: 'center', color: 'var(--slate-400)', padding: '24px' }}>No theses found</td></tr>}
              </tbody>
            </table>
          </div>
        </div>

        {/* Audit Log */}
        <div className="card">
          <div className="card-title">Recent Audit Log</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {recent_logs.slice(0, 6).map((log, i) => (
              <div key={log.id ?? i} style={{ display: 'flex', gap: 10, alignItems: 'flex-start' }}>
                <div style={{ width: 32, height: 32, background: 'var(--slate-100)', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, fontSize: 14 }}>
                  {log.event === 'created' ? '➕' : log.event === 'updated' ? '✏️' : log.event === 'deleted' ? '🗑️' : '📋'}
                </div>
                <div>
                  <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--slate-800)' }}>{log.user?.name ?? 'System'} · <span style={{ color: 'var(--slate-500)', fontWeight: 500 }}>{log.auditable_type?.split('\\').pop()} {log.event}</span></div>
                  <div style={{ fontSize: 10, color: 'var(--slate-400)', marginTop: 2 }}>{log.created_at ? new Date(log.created_at).toLocaleString() : ''}</div>
                </div>
              </div>
            ))}
            {recent_logs.length === 0 && <div style={{ color: 'var(--slate-400)', fontSize: 13, textAlign: 'center', padding: '20px 0' }}>No audit logs yet.</div>}
          </div>
        </div>
      </div>

      {/* Quick Admin Links */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 12, marginTop: 24 }}>
        {[
          { to: '/app/admin/users',  icon: '👤', label: 'Manage Users',   desc: 'Add, edit, manage accounts' },
          { to: '/app/admin/theses', icon: '📄', label: 'Thesis Registry', desc: 'All thesis records' },
          { to: '/app/repository',   icon: '📚', label: 'Repository',     desc: 'Published research' },
          { to: '/app/inbox',        icon: '📬', label: 'Inbox',          desc: 'System messages' },
        ].map(a => (
          <Link key={a.to} to={a.to} style={{ background: '#fff', border: '1px solid var(--slate-100)', borderRadius: 16, padding: '18px', textDecoration: 'none', display: 'block' }}>
            <div style={{ fontSize: 24, marginBottom: 10 }}>{a.icon}</div>
            <div style={{ fontWeight: 800, fontSize: 13, color: 'var(--slate-900)' }}>{a.label}</div>
            <div style={{ fontSize: 11, color: 'var(--slate-500)', marginTop: 4 }}>{a.desc}</div>
          </Link>
        ))}
      </div>
    </div>
  );
}

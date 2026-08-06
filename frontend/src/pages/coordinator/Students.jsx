import React, { useEffect, useState } from 'react';
import api from '../../api';

export default function CoordinatorStudents() {
  const [students, setStudents] = useState([]);
  const [loading, setLoading]   = useState(true);
  const [search, setSearch]     = useState('');

  useEffect(() => {
    api.get('/coordinator/students').then(r => {
      setStudents(r.data.data ?? r.data ?? []);
    }).catch(() => {}).finally(() => setLoading(false));
  }, []);

  const filtered = students.filter(s =>
    s.user?.name?.toLowerCase().includes(search.toLowerCase()) ||
    s.user?.email?.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  return (
    <div>
      <div className="page-header">
        <h1 className="page-title">Programme Students</h1>
        <p className="page-subtitle">All students registered under your assigned programmes.</p>
      </div>

      <div className="card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
          <div className="card-title" style={{ marginBottom: 0 }}>Student Registry ({filtered.length})</div>
          <div className="search-wrap">
            <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Search students…" value={search} onChange={e => setSearch(e.target.value)} />
          </div>
        </div>

        {filtered.length === 0 ? (
          <div className="empty-state">
            <div className="empty-icon">👥</div>
            <div className="empty-title">No students found</div>
          </div>
        ) : (
          <div className="tbl-wrap">
            <table>
              <thead>
                <tr><th>Student</th><th>Programme</th><th>Level</th><th>Cohort</th><th>Thesis</th><th>Status</th><th>Progress</th></tr>
              </thead>
              <tbody>
                {filtered.map(s => (
                  <tr key={s.id}>
                    <td>
                      <div style={{ fontWeight: 700 }}>{s.user?.name}</div>
                      <div style={{ fontSize: 11, color: 'var(--slate-400)' }}>{s.user?.email}</div>
                    </td>
                    <td style={{ fontSize: 12 }}>{s.program?.name ?? s.program?.code ?? '—'}</td>
                    <td style={{ fontSize: 12 }}>{s.level?.name ?? '—'}</td>
                    <td style={{ fontSize: 12 }}>{s.cohort?.name ?? '—'}</td>
                    <td style={{ fontSize: 12, maxWidth: 200 }}>{s.thesis?.title?.slice(0, 50) ?? <span style={{ color: 'var(--slate-300)' }}>Not registered</span>}</td>
                    <td><span className={`badge ${s.enrollment_status === 'active' ? 'green' : 'slate'}`}>{s.enrollment_status ?? '—'}</span></td>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <div className="progress-wrap" style={{ width: 80 }}>
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

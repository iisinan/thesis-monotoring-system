import React, { useEffect, useState, useCallback } from 'react';
import api from '../../api';

export default function AdminTheses() {
  const [theses, setTheses] = useState([]);
  const [total, setTotal]   = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage]     = useState(1);

  const load = useCallback(() => {
    setLoading(true);
    const params = new URLSearchParams({ page, ...(search && { search }), ...(status && { status }) });
    api.get(`/admin/theses?${params}`).then(r => {
      const d = r.data;
      setTheses(d.data ?? d);
      setTotal(d.total ?? (d.data ?? d).length);
    }).catch(() => {}).finally(() => setLoading(false));
  }, [search, status, page]);

  useEffect(() => { load(); }, [load]);

  const statusBadge = (s) => s === 'active' ? 'green' : s === 'completed' ? 'blue' : s === 'suspended' ? 'rose' : 'slate';

  return (
    <div>
      <div className="page-header">
        <h1 className="page-title">Thesis Registry</h1>
        <p className="page-subtitle">All thesis projects across all programmes and cohorts.</p>
      </div>

      <div className="card">
        <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap', alignItems: 'center' }}>
          <div className="search-wrap" style={{ flex: 1, maxWidth: 320 }}>
            <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Search by title…" value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} />
          </div>
          <select className="form-input" style={{ maxWidth: 180 }} value={status} onChange={e => { setStatus(e.target.value); setPage(1); }}>
            <option value="">All Statuses</option>
            <option value="proposed">Proposed</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="suspended">Suspended</option>
          </select>
          <span style={{ fontSize: 12, color: 'var(--slate-400)', fontWeight: 700, marginLeft: 'auto' }}>{total} theses</span>
        </div>

        {loading ? (
          <div className="loading-spinner"><div className="spinner" /></div>
        ) : (
          <div className="tbl-wrap">
            <table>
              <thead>
                <tr><th>Student</th><th>Title</th><th>Programme</th><th>Status</th><th>Progress</th></tr>
              </thead>
              <tbody>
                {theses.map(t => (
                  <tr key={t.id}>
                    <td style={{ fontWeight: 700 }}>{t.student?.user?.name ?? '—'}</td>
                    <td style={{ fontSize: 12, maxWidth: 300 }}>{t.title?.slice(0, 80) ?? '—'}{t.title?.length > 80 ? '…' : ''}</td>
                    <td style={{ fontSize: 12 }}>{t.student?.program?.code ?? '—'}</td>
                    <td><span className={`badge ${statusBadge(t.status)}`}>{t.status}</span></td>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <div className="progress-wrap" style={{ width: 80 }}>
                          <div className="progress-bar" style={{ width: `${t.progress_percentage ?? 0}%` }} />
                        </div>
                        <span style={{ fontSize: 11, fontWeight: 700, color: 'var(--green-600)' }}>{t.progress_percentage ?? 0}%</span>
                      </div>
                    </td>
                  </tr>
                ))}
                {theses.length === 0 && <tr><td colSpan={5} style={{ textAlign: 'center', padding: 32, color: 'var(--slate-400)' }}>No theses found.</td></tr>}
              </tbody>
            </table>
          </div>
        )}

        {total > 20 && (
          <div style={{ display: 'flex', justifyContent: 'center', gap: 8, marginTop: 20 }}>
            <button className="btn btn-outline btn-sm" disabled={page === 1} onClick={() => setPage(p => p - 1)}>← Prev</button>
            <span style={{ fontSize: 12, fontWeight: 700, padding: '8px 12px', color: 'var(--slate-500)' }}>Page {page}</span>
            <button className="btn btn-outline btn-sm" disabled={theses.length < 20} onClick={() => setPage(p => p + 1)}>Next →</button>
          </div>
        )}
      </div>
    </div>
  );
}

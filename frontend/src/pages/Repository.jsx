import React, { useEffect, useState } from 'react';
import api from '../api';

export default function Repository() {
  const [items, setItems]   = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    api.get('/repository').then(r => {
      setItems(r.data.data ?? r.data ?? []);
    }).catch(() => {}).finally(() => setLoading(false));
  }, []);

  const filtered = items.filter(t =>
    t.title?.toLowerCase().includes(search.toLowerCase()) ||
    t.student?.user?.name?.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  return (
    <div>
      <div className="page-header">
        <h1 className="page-title">Research Repository</h1>
        <p className="page-subtitle">Browse all published and completed thesis research.</p>
      </div>

      <div className="card">
        <div style={{ marginBottom: 20 }}>
          <div className="search-wrap">
            <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Search by title or author…" value={search} onChange={e => setSearch(e.target.value)} />
          </div>
        </div>

        {filtered.length === 0 ? (
          <div className="empty-state">
            <div className="empty-icon">📚</div>
            <div className="empty-title">No published theses yet</div>
            <div className="empty-desc">Completed thesis work will appear here once published.</div>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 16 }}>
            {filtered.map(t => (
              <div key={t.id} style={{ background: 'var(--slate-50)', border: '1px solid var(--slate-100)', borderRadius: 16, padding: 20 }}>
                <div style={{ fontSize: 10, fontWeight: 800, textTransform: 'uppercase', letterSpacing: '.1em', color: 'var(--green-600)', marginBottom: 8 }}>
                  {t.student?.program?.code ?? 'Research'} · {t.created_at ? new Date(t.created_at).getFullYear() : ''}
                </div>
                <h3 style={{ fontSize: 14, fontWeight: 800, color: 'var(--slate-900)', lineHeight: 1.4, marginBottom: 8 }}>{t.title}</h3>
                {t.abstract && <p style={{ fontSize: 12, color: 'var(--slate-500)', lineHeight: 1.5 }}>{t.abstract.slice(0, 140)}…</p>}
                <div style={{ marginTop: 14, paddingTop: 14, borderTop: '1px solid var(--slate-200)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontSize: 12, fontWeight: 700, color: 'var(--slate-700)' }}>{t.student?.user?.name ?? '—'}</span>
                  <span className="badge green">Published</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

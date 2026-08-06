import React, { useEffect, useState, useCallback } from 'react';
import api from '../../api';

export default function AdminUsers() {
  const [users, setUsers]   = useState([]);
  const [total, setTotal]   = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [role, setRole]     = useState('');
  const [page, setPage]     = useState(1);

  const load = useCallback(() => {
    setLoading(true);
    const params = new URLSearchParams({ page, ...(search && { search }), ...(role && { role }) });
    api.get(`/admin/users?${params}`).then(r => {
      const d = r.data;
      setUsers(d.data ?? d);
      setTotal(d.total ?? (d.data ?? d).length);
    }).catch(() => {}).finally(() => setLoading(false));
  }, [search, role, page]);

  useEffect(() => { load(); }, [load]);

  const ROLES = ['Student', 'Supervisor', 'Program Coordinator', 'Admin', 'Director', 'Internal Examiner'];

  return (
    <div>
      <div className="page-header">
        <h1 className="page-title">User Management</h1>
        <p className="page-subtitle">Manage all institutional accounts across roles.</p>
      </div>

      <div className="card">
        {/* Toolbar */}
        <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap', alignItems: 'center' }}>
          <div className="search-wrap" style={{ flex: 1, maxWidth: 320 }}>
            <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Search by name or email…" value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} />
          </div>
          <select className="form-input" style={{ maxWidth: 200 }} value={role} onChange={e => { setRole(e.target.value); setPage(1); }}>
            <option value="">All Roles</option>
            {ROLES.map(r => <option key={r} value={r}>{r}</option>)}
          </select>
          <span style={{ fontSize: 12, color: 'var(--slate-400)', fontWeight: 700, marginLeft: 'auto' }}>{total} users</span>
        </div>

        {loading ? (
          <div className="loading-spinner"><div className="spinner" /></div>
        ) : (
          <div className="tbl-wrap">
            <table>
              <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th></tr>
              </thead>
              <tbody>
                {users.map(u => (
                  <tr key={u.id}>
                    <td>
                      <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <div style={{ width: 32, height: 32, borderRadius: 8, background: 'var(--green-100)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800, fontSize: 12, color: 'var(--green-700)', flexShrink: 0 }}>
                          {u.name?.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()}
                        </div>
                        <span style={{ fontWeight: 700, fontSize: 13 }}>{u.name}</span>
                      </div>
                    </td>
                    <td style={{ fontSize: 12, color: 'var(--slate-500)' }}>{u.email}</td>
                    <td>
                      {u.roles?.map(r => (
                        <span key={r.id} className={`badge ${r.name === 'Admin' ? 'rose' : r.name === 'Student' ? 'green' : r.name === 'Supervisor' ? 'blue' : 'amber'}`} style={{ marginRight: 4 }}>
                          {r.name}
                        </span>
                      ))}
                    </td>
                    <td style={{ fontSize: 12, color: 'var(--slate-400)' }}>
                      {u.last_login_at ? new Date(u.last_login_at).toLocaleDateString() : 'Never'}
                    </td>
                    <td>
                      <span className={`badge ${u.is_active ? 'green' : 'slate'}`}>{u.is_active ? 'Active' : 'Inactive'}</span>
                    </td>
                  </tr>
                ))}
                {users.length === 0 && <tr><td colSpan={5} style={{ textAlign: 'center', padding: 32, color: 'var(--slate-400)' }}>No users found.</td></tr>}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {total > 20 && (
          <div style={{ display: 'flex', justifyContent: 'center', gap: 8, marginTop: 20 }}>
            <button className="btn btn-outline btn-sm" disabled={page === 1} onClick={() => setPage(p => p - 1)}>← Prev</button>
            <span style={{ fontSize: 12, fontWeight: 700, padding: '8px 12px', color: 'var(--slate-500)' }}>Page {page}</span>
            <button className="btn btn-outline btn-sm" disabled={users.length < 20} onClick={() => setPage(p => p + 1)}>Next →</button>
          </div>
        )}
      </div>
    </div>
  );
}

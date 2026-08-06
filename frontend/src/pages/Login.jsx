import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api, { setAuthToken, saveCurrentUser } from '../api';

const QUICK_LOGINS = [
  { label: 'Admin',               email: 'isinan@noun.edu.ng',       password: 'Sinan3367#',   role: 'Admin' },
  { label: 'Program Coordinator', email: 'coordinator@acetel.edu.ng', password: 'password',    role: 'Coordinator' },
  { label: 'Supervisor',          email: 'supervisor@acetel.edu.ng',  password: 'password',    role: 'Supervisor' },
  { label: 'Student',             email: 'student@acetel.edu.ng',     password: 'password',    role: 'Student' },
];

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const res = await api.post('/login', { email, password });
      const { token, user, role } = res.data;
      setAuthToken(token);
      saveCurrentUser({ ...user, role });
      navigate('/app');
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid credentials. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const quickLogin = (q) => { setEmail(q.email); setPassword(q.password); };

  return (
    <div className="auth-shell">
      {/* ── Left panel ─────────────────────────────── */}
      <div className="auth-left">
        <div style={{ position: 'relative', zIndex: 1 }}>
          <div className="auth-logo-text">ACETEL TMS</div>
          <div style={{ fontSize: 11, color: 'rgba(255,255,255,.6)', marginTop: 4, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.1em' }}>Thesis Monitoring System</div>
        </div>

        <div style={{ position: 'relative', zIndex: 1 }}>
          <h1 className="auth-headline">Track Every Milestone of Your Research Journey</h1>
          <p className="auth-desc">A comprehensive platform for managing postgraduate thesis supervision, milestone tracking, and academic progress reporting.</p>
        </div>

        <div style={{ position: 'relative', zIndex: 1 }}>
          <p style={{ fontSize: 10, fontWeight: 800, textTransform: 'uppercase', letterSpacing: '.1em', color: 'rgba(255,255,255,.5)', marginBottom: 10 }}>Quick Sign-In</p>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            {QUICK_LOGINS.map(q => (
              <button key={q.label} className="quick-login-btn" onClick={() => quickLogin(q)}>
                <div className="quick-title">{q.label}</div>
                <div className="quick-email">{q.email}</div>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* ── Right panel ────────────────────────────── */}
      <div className="auth-right">
        <div style={{ marginBottom: 36 }}>
          <h2 style={{ fontSize: 24, fontWeight: 900, color: 'var(--slate-900)' }}>Sign In</h2>
          <p style={{ fontSize: 13, color: 'var(--slate-500)', marginTop: 6 }}>Enter your institutional credentials to access the portal.</p>
        </div>

        {error && (
          <div style={{ background: '#fff1f2', border: '1px solid #fecdd3', borderRadius: 10, padding: '10px 14px', fontSize: 12, color: '#9f1239', marginBottom: 20 }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Email Address</label>
            <input className="form-input" type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="you@institution.edu.ng" required />
          </div>
          <div className="form-group" style={{ marginBottom: 24 }}>
            <label className="form-label">Password</label>
            <input className="form-input" type="password" value={password} onChange={e => setPassword(e.target.value)} placeholder="••••••••••" required />
          </div>
          <button className="btn btn-primary" type="submit" disabled={loading} style={{ width: '100%', justifyContent: 'center', padding: '14px 20px' }}>
            {loading ? 'Authenticating…' : 'Sign In to Portal'}
          </button>
        </form>

        <div style={{ marginTop: 32, padding: '20px', background: 'var(--slate-50)', borderRadius: 12, border: '1px solid var(--slate-100)' }}>
          <p style={{ fontSize: 11, fontWeight: 700, color: 'var(--slate-500)', marginBottom: 8 }}>NEED HELP?</p>
          <p style={{ fontSize: 12, color: 'var(--slate-600)' }}>Contact your program coordinator or IT support for login assistance.</p>
        </div>
      </div>
    </div>
  );
}

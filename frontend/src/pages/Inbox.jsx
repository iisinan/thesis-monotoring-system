import React, { useEffect, useState } from 'react';
import api from '../api';

export default function Inbox() {
  const [messages, setMessages] = useState([]);
  const [loading, setLoading]   = useState(true);
  const [compose, setCompose]   = useState(false);
  const [toEmail, setToEmail]   = useState('');
  const [subject, setSubject]   = useState('');
  const [body, setBody]         = useState('');
  const [sending, setSending]   = useState(false);
  const [sent, setSent]         = useState('');

  useEffect(() => {
    api.get('/inbox').then(r => {
      setMessages(r.data.data ?? r.data ?? []);
    }).catch(() => {}).finally(() => setLoading(false));
  }, []);

  const handleSend = async (e) => {
    e.preventDefault();
    setSending(true);
    setSent('');
    try {
      await api.post('/messages', { to_email: toEmail, subject, body });
      setSent('✅ Message sent successfully!');
      setCompose(false); setToEmail(''); setSubject(''); setBody('');
    } catch (err) {
      setSent('❌ ' + (err.response?.data?.message ?? 'Failed to send message.'));
    } finally { setSending(false); }
  };

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  return (
    <div>
      <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h1 className="page-title">Inbox</h1>
          <p className="page-subtitle">Direct messages and communications.</p>
        </div>
        <button className="btn btn-primary" onClick={() => setCompose(true)}>✍️ Compose</button>
      </div>

      {sent && (
        <div style={{ background: sent.startsWith('✅') ? 'var(--green-50)' : '#fff1f2', border: `1px solid ${sent.startsWith('✅') ? 'var(--green-200)' : '#fecdd3'}`, borderRadius: 10, padding: '12px 16px', fontSize: 13, marginBottom: 20, color: sent.startsWith('✅') ? 'var(--green-700)' : '#9f1239' }}>
          {sent}
        </div>
      )}

      {/* Compose Modal */}
      {compose && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,.5)', zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <div style={{ background: '#fff', borderRadius: 20, padding: 32, width: '100%', maxWidth: 480, boxShadow: '0 24px 60px rgba(0,0,0,.2)' }}>
            <h3 style={{ fontWeight: 900, marginBottom: 20 }}>New Message</h3>
            <form onSubmit={handleSend}>
              <div className="form-group">
                <label className="form-label">Recipient Email</label>
                <input className="form-input" type="email" value={toEmail} onChange={e => setToEmail(e.target.value)} placeholder="recipient@institution.edu.ng" required />
              </div>
              <div className="form-group">
                <label className="form-label">Subject</label>
                <input className="form-input" value={subject} onChange={e => setSubject(e.target.value)} placeholder="Message subject…" required />
              </div>
              <div className="form-group">
                <label className="form-label">Message</label>
                <textarea className="form-input form-textarea" rows={5} value={body} onChange={e => setBody(e.target.value)} placeholder="Write your message…" required />
              </div>
              <div style={{ display: 'flex', gap: 10 }}>
                <button className="btn btn-primary" type="submit" disabled={sending} style={{ flex: 1, justifyContent: 'center' }}>
                  {sending ? 'Sending…' : '📤 Send Message'}
                </button>
                <button className="btn btn-outline" type="button" onClick={() => setCompose(false)}>Cancel</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {messages.length === 0 ? (
        <div className="card">
          <div className="empty-state">
            <div className="empty-icon">📬</div>
            <div className="empty-title">No messages yet</div>
            <div className="empty-desc">Your inbox is empty. Use Compose to start a conversation.</div>
          </div>
        </div>
      ) : (
        <div className="card">
          <div className="tbl-wrap">
            <table>
              <thead><tr><th>From</th><th>Subject</th><th>Date</th><th>Status</th></tr></thead>
              <tbody>
                {messages.map((m, i) => (
                  <tr key={m.id ?? i}>
                    <td style={{ fontWeight: 700 }}>{m.sender?.name ?? m.from_name ?? '—'}</td>
                    <td style={{ fontSize: 13 }}>{m.subject ?? m.body?.slice(0, 60) ?? '—'}</td>
                    <td style={{ fontSize: 12, color: 'var(--slate-400)' }}>{m.created_at ? new Date(m.created_at).toLocaleDateString() : '—'}</td>
                    <td><span className={`badge ${m.read_at ? 'slate' : 'green'}`}>{m.read_at ? 'Read' : 'New'}</span></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}

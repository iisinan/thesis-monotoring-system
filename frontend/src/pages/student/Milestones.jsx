import React, { useEffect, useState } from 'react';
import api from '../../api';

export default function StudentMilestones() {
  const [milestones, setMilestones]   = useState([]);
  const [thesis, setThesis]           = useState(null);
  const [loading, setLoading]         = useState(true);
  const [submitting, setSubmitting]   = useState(false);
  const [activeMid, setActiveMid]     = useState(null);
  const [selectedFile, setSelectedFile] = useState(null);
  const [selectedPpt, setSelectedPpt]   = useState(null);
  const [description, setDescription]   = useState('');
  const [message, setMessage]           = useState('');

  const loadData = () => {
    setLoading(true);
    api.get('/milestones').then(r => {
      setMilestones(r.data.milestones ?? []);
      setThesis(r.data.thesis);
    }).catch(() => {}).finally(() => setLoading(false));
  };

  useEffect(loadData, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!activeMid) return;
    setSubmitting(true);
    setMessage('');
    const fd = new FormData();
    if (selectedFile) fd.append('file', selectedFile);
    if (selectedPpt)  fd.append('ppt',  selectedPpt);
    fd.append('description', description);
    try {
      await api.post(`/milestones/${activeMid}/submit`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      setMessage('✅ Milestone submitted successfully!');
      setSelectedFile(null); setSelectedPpt(null); setDescription(''); setActiveMid(null);
      loadData();
    } catch (err) {
      setMessage('❌ ' + (err.response?.data?.message ?? 'Submission failed.'));
    } finally { setSubmitting(false); }
  };

  const statusColor = (s) => s === 'approved' ? 'green' : s === 'submitted' ? 'blue' : s === 'revision_required' ? 'rose' : 'slate';
  const statusLabel = (s) => (s ?? 'not_started').replace(/_/g, ' ');

  if (loading) return <div className="loading-spinner"><div className="spinner" /></div>;

  return (
    <div>
      <div className="page-header">
        <h1 className="page-title">Academic Milestones</h1>
        {thesis && <p className="page-subtitle">{thesis.title}</p>}
      </div>

      {message && (
        <div style={{ background: message.startsWith('✅') ? 'var(--green-50)' : '#fff1f2', border: `1px solid ${message.startsWith('✅') ? 'var(--green-200)' : '#fecdd3'}`, borderRadius: 10, padding: '12px 16px', fontSize: 13, marginBottom: 20, color: message.startsWith('✅') ? 'var(--green-700)' : '#9f1239' }}>
          {message}
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: 24 }}>
        {/* Milestone timeline */}
        <div className="card">
          <div className="card-title">Milestones Timeline</div>
          <div className="milestone-list">
            {milestones.map(m => (
              <div key={m.id} className="milestone-item" onClick={() => setActiveMid(m.id)} style={{ cursor: ['not_started','submitted','revision_required'].includes(m.status) ? 'pointer' : 'default', borderRadius: 10, padding: '10px 8px', background: activeMid === m.id ? 'var(--green-50)' : 'transparent' }}>
                <div className={`m-dot ${m.status === 'approved' ? 'done' : m.status === 'submitted' ? 'submitted' : 'pending'}`}>
                  {m.status === 'approved'
                    ? <svg style={{width:14,height:14}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3"><polyline points="20 6 9 17 4 12"/></svg>
                    : <svg style={{width:14,height:14}} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  }
                </div>
                <div className="m-info">
                  <div className="m-name">{m.template?.name ?? `Milestone ${m.template?.order}`}</div>
                  <div style={{ display: 'flex', gap: 8, marginTop: 4, alignItems: 'center' }}>
                    <span className={`badge ${statusColor(m.status)}`}>{statusLabel(m.status)}</span>
                    <span style={{ fontSize: 9, color: 'var(--slate-400)', fontWeight: 700 }}>#{m.template?.order}</span>
                  </div>
                  {m.approved_at && <div style={{ fontSize: 10, color: 'var(--green-600)', fontWeight: 700, marginTop: 4 }}>Approved {m.approved_at}</div>}
                </div>
              </div>
            ))}
            {milestones.length === 0 && <div className="empty-state"><div className="empty-title">No milestones found</div><div className="empty-desc">Contact your coordinator if this is unexpected.</div></div>}
          </div>
        </div>

        {/* Submission panel */}
        <div className="card">
          <div className="card-title">Submit Milestone Artifacts</div>
          {activeMid ? (
            <form onSubmit={handleSubmit}>
              <div style={{ background: 'var(--green-50)', border: '1px solid var(--green-100)', borderRadius: 10, padding: '10px 14px', marginBottom: 16, fontSize: 12, color: 'var(--green-700)', fontWeight: 700 }}>
                Submitting: {milestones.find(m => m.id === activeMid)?.template?.name}
              </div>

              <div className="form-group">
                <label className="form-label">PPT / Slide Deck (PDF or PPTX)</label>
                <div className="upload-zone">
                  <div style={{ fontSize: 24 }}>📊</div>
                  <div className="upload-zone-label">{selectedPpt ? selectedPpt.name : 'Click to select PPT file'}</div>
                  <input type="file" accept=".ppt,.pptx,.pdf" onChange={e => setSelectedPpt(e.target.files[0])} />
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Manuscript (PDF or DOCX)</label>
                <div className="upload-zone">
                  <div style={{ fontSize: 24 }}>📄</div>
                  <div className="upload-zone-label">{selectedFile ? selectedFile.name : 'Click to select manuscript'}</div>
                  <input type="file" accept=".pdf,.doc,.docx" onChange={e => setSelectedFile(e.target.files[0])} />
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Remarks / Notes</label>
                <textarea className="form-input form-textarea" rows={3} value={description} onChange={e => setDescription(e.target.value)} placeholder="Add comments for your supervisor…" />
              </div>

              <div style={{ display: 'flex', gap: 10 }}>
                <button className="btn btn-primary" type="submit" disabled={submitting} style={{ flex: 1, justifyContent: 'center' }}>
                  {submitting ? 'Submitting…' : '↑ Submit Milestone'}
                </button>
                <button className="btn btn-outline" type="button" onClick={() => setActiveMid(null)}>Cancel</button>
              </div>
            </form>
          ) : (
            <div className="empty-state">
              <div className="empty-icon">👆</div>
              <div className="empty-title">Select a milestone</div>
              <div className="empty-desc">Click a milestone on the left to submit documents for it.</div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

import React from 'react';
import { useNavigate } from 'react-router-dom';

export default function Welcome() {
  const navigate = useNavigate();

  const features = [
    { icon: '📋', name: 'Milestone Tracking', desc: 'Monitor every research milestone from proposal to graduation.' },
    { icon: '👥', name: 'Supervision Hub',    desc: 'Streamlined communication between students and supervisors.' },
    { icon: '📊', name: 'Progress Analytics', desc: 'Real-time programme analytics and reporting for coordinators.' },
    { icon: '📁', name: 'Repository',         desc: 'Publish and browse completed thesis research.' },
    { icon: '📬', name: 'Inbox System',       desc: 'Direct messaging with file attachment support.' },
    { icon: '🎓', name: 'Defence Events',     desc: 'Schedule and manage proposal and internal defence sessions.' },
  ];

  return (
    <div className="welcome-hero">
      {/* Nav */}
      <div className="welcome-nav" style={{ position: 'relative', zIndex: 1 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div style={{ width: 40, height: 40, background: 'rgba(255,255,255,.2)', borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontWeight: 900, fontSize: 18 }}>T</div>
          <div>
            <div style={{ color: '#fff', fontWeight: 900, fontSize: 16, lineHeight: 1 }}>ACETEL TMS</div>
            <div style={{ color: 'rgba(255,255,255,.6)', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.1em' }}>Thesis Monitoring System</div>
          </div>
        </div>
        <button className="btn" onClick={() => navigate('/login')} style={{ background: 'rgba(255,255,255,.15)', color: '#fff', border: '1px solid rgba(255,255,255,.25)' }}>
          Sign In →
        </button>
      </div>

      {/* Hero */}
      <div className="welcome-hero-content" style={{ position: 'relative', zIndex: 1 }}>
        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'rgba(255,255,255,.1)', border: '1px solid rgba(255,255,255,.2)', borderRadius: 30, padding: '6px 16px', marginBottom: 24 }}>
          <span style={{ width: 8, height: 8, background: '#4ade80', borderRadius: '50%', display: 'inline-block', animation: 'pulse 2s infinite' }}></span>
          <span style={{ fontSize: 11, fontWeight: 700, color: 'rgba(255,255,255,.9)', textTransform: 'uppercase', letterSpacing: '.1em' }}>ACETEL Postgraduate Research Portal</span>
        </div>

        <h1 className="welcome-big-title">
          Your Research Journey,<br /><span>Fully Tracked</span>
        </h1>
        <p style={{ color: 'rgba(255,255,255,.75)', fontSize: 16, maxWidth: 500, lineHeight: 1.6, marginTop: 20 }}>
          The official thesis monitoring platform for ACETEL postgraduate students, supervisors, and programme coordinators.
        </p>

        <div style={{ marginTop: 32, display: 'flex', gap: 12, flexWrap: 'wrap', justifyContent: 'center' }}>
          <button className="btn" onClick={() => navigate('/login')} style={{ background: '#fff', color: 'var(--green-700)', padding: '14px 28px', fontSize: 13 }}>
            Access Portal →
          </button>
          <button className="btn" style={{ background: 'rgba(255,255,255,.12)', color: '#fff', border: '1px solid rgba(255,255,255,.25)', padding: '14px 28px', fontSize: 13 }}>
            Learn More
          </button>
        </div>

        <div className="welcome-features">
          {features.map(f => (
            <div key={f.name} className="feature-card">
              <div className="feature-icon">{f.icon}</div>
              <div className="feature-name">{f.name}</div>
              <div className="feature-desc">{f.desc}</div>
            </div>
          ))}
        </div>
      </div>

      {/* Footer */}
      <div style={{ textAlign: 'center', padding: '24px', color: 'rgba(255,255,255,.4)', fontSize: 12, position: 'relative', zIndex: 1 }}>
        © 2026 ACETEL – National Open University of Nigeria | Postgraduate Research Division
      </div>
    </div>
  );
}

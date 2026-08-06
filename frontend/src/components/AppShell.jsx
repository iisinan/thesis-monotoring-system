import React from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { getCurrentUser, setAuthToken, saveCurrentUser } from '../api';
import api from '../api';

const icons = {
  overview:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>,
  milestones:  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>,
  students:    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>,
  users:       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>,
  theses:      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>,
  inbox:       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>,
  repo:        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>,
  logout:      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>,
};

function NavSection({ label }) {
  return <div className="sidebar-section">{label}</div>;
}

function NavBtn({ to, icon, label }) {
  return (
    <NavLink to={to} className={({ isActive }) => `nav-item${isActive ? ' active' : ''}`}>
      {icons[icon]} {label}
    </NavLink>
  );
}

export default function AppShell() {
  const user     = getCurrentUser();
  const role     = user?.role ?? '';
  const navigate = useNavigate();

  const handleLogout = async () => {
    try { await api.post('/logout'); } catch {}
    setAuthToken(null);
    saveCurrentUser(null);
    navigate('/login');
  };

  const initials = (user?.name ?? 'U').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();

  return (
    <div className="app-shell">
      {/* ── Sidebar ─────────────────────────────────── */}
      <aside className="sidebar">
        <div className="sidebar-logo">
          <div className="sidebar-brand">
            <div className="sidebar-icon">T</div>
            <div>
              <div className="sidebar-name">ACETEL TMS</div>
              <div className="sidebar-sub">Thesis Portal</div>
            </div>
          </div>
        </div>

        <nav className="sidebar-nav">
          <NavSection label="Main" />
          <NavBtn to="/app" icon="overview" label="Dashboard" />

          {role === 'Student' && <>
            <NavSection label="Research" />
            <NavBtn to="/app/milestones" icon="milestones" label="My Milestones" />
            <NavBtn to="/app/inbox"      icon="inbox"      label="Inbox" />
            <NavBtn to="/app/repository" icon="repo"       label="Repository" />
          </>}

          {role === 'Supervisor' && <>
            <NavSection label="Students" />
            <NavBtn to="/app/supervisor/students" icon="students" label="My Students" />
            <NavBtn to="/app/inbox"               icon="inbox"    label="Inbox" />
            <NavBtn to="/app/repository"          icon="repo"     label="Repository" />
          </>}

          {role === 'Program Coordinator' && <>
            <NavSection label="Programme" />
            <NavBtn to="/app/coordinator"          icon="overview"  label="Programme Overview" />
            <NavBtn to="/app/coordinator/students" icon="students"  label="Students" />
            <NavBtn to="/app/inbox"                icon="inbox"     label="Inbox" />
            <NavBtn to="/app/repository"           icon="repo"      label="Repository" />
          </>}

          {(role === 'Admin' || role === 'Director') && <>
            <NavSection label="Administration" />
            <NavBtn to="/app/admin"        icon="overview" label="System Overview" />
            <NavBtn to="/app/admin/users"  icon="users"    label="User Management" />
            <NavBtn to="/app/admin/theses" icon="theses"   label="Thesis Registry" />
            <NavBtn to="/app/repository"   icon="repo"     label="Repository" />
            <NavBtn to="/app/inbox"        icon="inbox"    label="Inbox" />
          </>}
        </nav>

        <div className="sidebar-user">
          <div className="sidebar-user-card">
            <div className="user-avatar">{initials}</div>
            <div>
              <div className="user-name">{user?.name}</div>
              <div className="user-role">{role}</div>
            </div>
            <button className="logout-btn" onClick={handleLogout} title="Log out">
              {icons.logout}
            </button>
          </div>
        </div>
      </aside>

      {/* ── Main content ────────────────────────────── */}
      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}

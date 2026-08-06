import React, { Suspense, lazy, useEffect } from 'react';
import './index.css';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { getCurrentUser } from './api';

// ── Lazy-loaded pages (code splitting reduces initial bundle) ──────
const Welcome   = lazy(() => import('./pages/Welcome'));
const Login     = lazy(() => import('./pages/Login'));
const AppShell  = lazy(() => import('./components/AppShell'));

const StudentDashboard      = lazy(() => import('./pages/student/Dashboard'));
const StudentMilestones     = lazy(() => import('./pages/student/Milestones'));
const SupervisorDashboard   = lazy(() => import('./pages/supervisor/Dashboard'));
const CoordinatorDashboard  = lazy(() => import('./pages/coordinator/Dashboard'));
const CoordinatorStudents   = lazy(() => import('./pages/coordinator/Students'));
const AdminDashboard        = lazy(() => import('./pages/admin/Dashboard'));
const AdminUsers            = lazy(() => import('./pages/admin/Users'));
const AdminTheses           = lazy(() => import('./pages/admin/Theses'));
const Repository            = lazy(() => import('./pages/Repository'));
const Inbox                 = lazy(() => import('./pages/Inbox'));

// ── Loading fallback ───────────────────────────────────────────────
function PageLoader() {
  return (
    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh', flexDirection: 'column', gap: 16 }}>
      <div style={{ width: 40, height: 40, border: '3px solid #e2e8f0', borderTopColor: '#16a34a', borderRadius: '50%', animation: 'spin .7s linear infinite' }} />
      <span style={{ fontSize: 12, fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '.1em' }}>Loading…</span>
    </div>
  );
}

// ── Route guards ───────────────────────────────────────────────────
function RequireAuth({ children }) {
  const user = getCurrentUser();
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function RoleDashboard() {
  const user = getCurrentUser();
  if (!user) return <Navigate to="/login" replace />;
  const role = user.role;
  if (role === 'Student')              return <StudentDashboard />;
  if (role === 'Supervisor')           return <SupervisorDashboard />;
  if (role === 'Program Coordinator')  return <CoordinatorDashboard />;
  if (role === 'Admin' || role === 'Director') return <AdminDashboard />;
  return <StudentDashboard />;
}

export default function App() {
  useEffect(() => {
    const onExpire = () => { window.location.href = '/login'; };
    window.addEventListener('auth_session_expired', onExpire);
    return () => window.removeEventListener('auth_session_expired', onExpire);
  }, []);

  return (
    <BrowserRouter>
      <Suspense fallback={<PageLoader />}>
        <Routes>
          <Route path="/"      element={<Welcome />} />
          <Route path="/login" element={<Login />} />

          <Route path="/app" element={<RequireAuth><AppShell /></RequireAuth>}>
            <Route index                       element={<RoleDashboard />} />
            <Route path="milestones"           element={<StudentMilestones />} />
            <Route path="supervisor/students"  element={<SupervisorDashboard />} />
            <Route path="coordinator"          element={<CoordinatorDashboard />} />
            <Route path="coordinator/students" element={<CoordinatorStudents />} />
            <Route path="admin"                element={<AdminDashboard />} />
            <Route path="admin/users"          element={<AdminUsers />} />
            <Route path="admin/theses"         element={<AdminTheses />} />
            <Route path="repository"           element={<Repository />} />
            <Route path="inbox"                element={<Inbox />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}

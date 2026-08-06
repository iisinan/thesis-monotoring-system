import React, { useEffect } from 'react';
import './index.css';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { getCurrentUser } from './api';

// Pages
import Welcome   from './pages/Welcome';
import Login     from './pages/Login';
import AppShell  from './components/AppShell';

// Role dashboards & pages
import StudentDashboard     from './pages/student/Dashboard';
import StudentMilestones    from './pages/student/Milestones';
import SupervisorDashboard  from './pages/supervisor/Dashboard';
import CoordinatorDashboard from './pages/coordinator/Dashboard';
import CoordinatorStudents  from './pages/coordinator/Students';
import AdminDashboard       from './pages/admin/Dashboard';
import AdminUsers           from './pages/admin/Users';
import AdminTheses          from './pages/admin/Theses';
import Repository           from './pages/Repository';
import Inbox                from './pages/Inbox';

function RequireAuth({ children }) {
  const user = getCurrentUser();
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function RoleDashboard() {
  const user = getCurrentUser();
  if (!user) return <Navigate to="/login" replace />;
  const role = user.role;
  if (role === 'Student')           return <StudentDashboard />;
  if (role === 'Supervisor')        return <SupervisorDashboard />;
  if (role === 'Program Coordinator') return <CoordinatorDashboard />;
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
      <Routes>
        <Route path="/"       element={<Welcome />} />
        <Route path="/login"  element={<Login />} />

        {/* Protected app routes */}
        <Route path="/app" element={<RequireAuth><AppShell /></RequireAuth>}>
          <Route index                      element={<RoleDashboard />} />
          <Route path="milestones"          element={<StudentMilestones />} />
          <Route path="supervisor/students" element={<SupervisorDashboard />} />
          <Route path="coordinator"         element={<CoordinatorDashboard />} />
          <Route path="coordinator/students"element={<CoordinatorStudents />} />
          <Route path="admin"               element={<AdminDashboard />} />
          <Route path="admin/users"         element={<AdminUsers />} />
          <Route path="admin/theses"        element={<AdminTheses />} />
          <Route path="repository"          element={<Repository />} />
          <Route path="inbox"               element={<Inbox />} />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

import React, { useState, useEffect } from 'react';
import Welcome from './pages/Welcome';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import { getCurrentUser } from './api';

export default function App() {
  const [currentView, setCurrentView] = useState('welcome');
  const [user, setUser] = useState(null);

  useEffect(() => {
    // Check if user has an active session on mount
    const token = localStorage.getItem('auth_token');
    const storedUser = getCurrentUser();
    
    if (token && storedUser) {
      setUser(storedUser);
      setCurrentView('dashboard');
    }

    // Register session expiration listener
    const handleExpiration = () => {
      handleLogout();
      alert('Your security session has expired. Please sign in again.');
    };
    window.addEventListener('auth_session_expired', handleExpiration);
    return () => window.removeEventListener('auth_session_expired', handleExpiration);
  }, []);

  const handleLoginSuccess = (authenticatedUser) => {
    setUser(authenticatedUser);
    setCurrentView('dashboard');
  };

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('current_user');
    setUser(null);
    setCurrentView('welcome');
  };

  if (currentView === 'dashboard' && user) {
    return <Dashboard user={user} onLogout={handleLogout} />;
  }

  if (currentView === 'login') {
    return <Login onLoginSuccess={handleLoginSuccess} onNavigateToHome={() => setCurrentView('welcome')} />;
  }

  return <Welcome onNavigateToLogin={() => setCurrentView('login')} />;
}

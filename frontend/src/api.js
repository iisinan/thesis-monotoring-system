import axios from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' },
  withCredentials: false,
});

// Attach Bearer Token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
}, Promise.reject);

// Handle 401
api.interceptors.response.use(
  (r) => r,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('current_user');
      window.dispatchEvent(new Event('auth_session_expired'));
    }
    return Promise.reject(error);
  }
);

export const setAuthToken  = (t) => t ? localStorage.setItem('auth_token', t) : localStorage.removeItem('auth_token');
export const saveCurrentUser = (u) => u ? localStorage.setItem('current_user', JSON.stringify(u)) : localStorage.removeItem('current_user');
export const getCurrentUser  = () => { try { return JSON.parse(localStorage.getItem('current_user')); } catch { return null; } };

export default api;

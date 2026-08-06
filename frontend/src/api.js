import axios from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL || 'https://thesis-monotoring-system.onrender.com/api';

const api = axios.create({
  baseURL: BASE_URL,
  headers: { 
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 30000, // 30s timeout (Render cold starts can be slow)
  withCredentials: false,
});

// ── Bearer token attachment ─────────────────────────────────────────
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
}, Promise.reject);

// ── Auto logout on 401 ──────────────────────────────────────────────
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

// ── Keep-alive ping every 4 minutes ──────────────────────────────────
// Prevents Render free tier from sleeping (sleeps after 15 min inactivity)
let pingInterval = null;

export const startKeepAlive = () => {
  if (pingInterval) return; // already running
  pingInterval = setInterval(() => {
    axios.get(`${BASE_URL}/ping`, { timeout: 5000 }).catch(() => {});
  }, 4 * 60 * 1000); // every 4 minutes
};

export const stopKeepAlive = () => {
  if (pingInterval) { clearInterval(pingInterval); pingInterval = null; }
};

// ── Auth helpers ────────────────────────────────────────────────────
export const setAuthToken = (t) => {
  if (t) {
    localStorage.setItem('auth_token', t);
    startKeepAlive(); // begin pinging when user logs in
  } else {
    localStorage.removeItem('auth_token');
    stopKeepAlive();
  }
};

export const saveCurrentUser  = (u) => u ? localStorage.setItem('current_user', JSON.stringify(u)) : localStorage.removeItem('current_user');
export const getCurrentUser   = () => { try { return JSON.parse(localStorage.getItem('current_user')); } catch { return null; } };

// Start keep-alive immediately if already authenticated
if (localStorage.getItem('auth_token')) startKeepAlive();

export default api;

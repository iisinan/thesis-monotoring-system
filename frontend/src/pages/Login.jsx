import React, { useState } from 'react';
import api, { setAuthToken, saveCurrentUser } from '../api';
import { 
  Lock, Mail, Loader2, CheckCircle2, 
  MessageSquare, Zap, Eye, EyeOff, ArrowLeft, ArrowRight, BookOpen 
} from 'lucide-react';

export default function Login({ onLoginSuccess, onNavigateToHome }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await api.post('/login', { email, password });
      const { token, user } = response.data;
      
      setAuthToken(token);
      saveCurrentUser(user);
      onLoginSuccess(user);
    } catch (err) {
      console.error(err);
      setError(
        err.response?.data?.message || 
        err.response?.data?.error || 
        'Failed to connect to authentication server. Verify backend is running.'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleQuickLogin = (quickEmail, quickPassword = 'password') => {
    setEmail(quickEmail);
    setPassword(quickPassword);
  };

  return (
    <div className="min-h-screen flex bg-white font-sans">
      
      {/* ===== LEFT PANEL: Branding ===== */}
      <div className="hidden lg:flex lg:w-5/12 xl:w-1/2 relative overflow-hidden flex-col">
        {/* Modern white/green transition */}
        <div className="absolute inset-0 bg-white border-r border-emerald-50"></div>
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-white/0"></div>

        {/* Sophisticated decorative elements */}
        <div className="absolute top-0 right-0 w-[40rem] h-[40rem] bg-emerald-100/20 blur-[100px] rounded-full -mr-32 -mt-32 pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 w-80 h-80 bg-emerald-50/30 blur-[80px] rounded-full -ml-32 -mb-32 pointer-events-none"></div>

        {/* Content */}
        <div className="relative z-10 flex flex-col h-full p-12 xl:p-16">
          {/* Logo */}
          <button onClick={onNavigateToHome} className="flex items-center gap-3 group w-fit text-left focus:outline-none">
            <div className="w-12 h-12 rounded-2xl bg-white border border-emerald-100 shadow-sm flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-all">
              <span className="font-black text-emerald-600 text-lg">A</span>
            </div>
            <div>
              <span className="block text-base font-black text-slate-905 leading-none">ACETEL TMS</span>
              <span className="block text-[10px] font-black text-emerald-600 uppercase tracking-widest mt-1">Research Excellence</span>
            </div>
          </button>

          {/* Main Copy */}
          <div className="flex-grow flex flex-col justify-center">
            <div className="mb-8">
              <span className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-black uppercase tracking-wider shadow-sm">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Secure Institutional Access
              </span>
            </div>
            
            <h1 className="text-4xl xl:text-6xl font-black text-slate-900 leading-[1.1] mb-8 tracking-tighter">
              Elevate <span className="text-emerald-600">Postgraduate</span><br />
              Research Experience.
            </h1>
            
            <p className="text-slate-500 text-base xl:text-lg leading-relaxed max-w-sm font-medium">
              The professional platform for tracking thesis milestones and orchestrating collaboration between students and supervisors.
            </p>

            {/* Feature pills */}
            <div className="mt-12 space-y-5">
              <div className="flex items-center gap-4 group">
                <div className="w-10 h-10 rounded-xl bg-white border border-emerald-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                </div>
                <p className="text-xs font-black text-slate-600 uppercase tracking-wider">Milestone Intelligence</p>
              </div>
              <div className="flex items-center gap-4 group">
                <div className="w-10 h-10 rounded-xl bg-white border border-emerald-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <MessageSquare className="w-5 h-5 text-emerald-600" />
                </div>
                <p className="text-xs font-black text-slate-600 uppercase tracking-wider">Faculty Collaboration</p>
              </div>
              <div className="flex items-center gap-4 group">
                <div className="w-10 h-10 rounded-xl bg-white border border-emerald-100 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <Zap className="w-5 h-5 text-emerald-600" />
                </div>
                <p className="text-xs font-black text-slate-600 uppercase tracking-wider">Real-time Synchronization</p>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="border-t border-slate-100 pt-6">
            <p className="text-slate-400 text-[10px] font-black uppercase tracking-widest">&copy; 2026 ACETEL TRADEMARK RESOURCE</p>
          </div>
        </div>
      </div>

      {/* ===== RIGHT PANEL: Form ===== */}
      <div className="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-12 xl:px-20 bg-slate-50">
        
        {/* Mobile logo (only visible on small screens) */}
        <button onClick={onNavigateToHome} className="lg:hidden mb-10 flex items-center gap-3 focus:outline-none">
          <div className="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0 bg-white border border-emerald-100 flex items-center justify-center shadow-sm">
            <span className="font-black text-emerald-600 text-sm">A</span>
          </div>
          <div className="text-left">
            <span className="block text-sm font-black text-slate-900 leading-none">ACETEL TMS</span>
            <span className="block text-[9px] font-semibold text-emerald-600 uppercase tracking-widest mt-0.5">Thesis Monitoring</span>
          </div>
        </button>

        <div className="w-full max-w-md">
          {/* Heading */}
          <div className="mb-10">
            <h2 className="text-3xl font-black text-slate-900 tracking-tight mb-2">Sign in</h2>
            <p className="text-slate-500 text-sm">Enter your credentials to access your dashboard.</p>
          </div>

          {error && (
            <div className="mb-6 flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-700">
              <div className="w-8 h-8 rounded-xl bg-red-500 flex items-center justify-center flex-shrink-0 text-white">
                <Lock className="w-4 h-4" />
              </div>
              <div>
                <p className="text-sm font-bold mb-1">Sign in failed</p>
                <p className="text-xs text-red-500">{error}</p>
              </div>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Email */}
            <div className="space-y-1.5">
              <label htmlFor="email" className="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                Email Address
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <Mail className="w-4 h-4 text-slate-400" />
                </div>
                <input
                  id="email"
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@acetel.edu.ng"
                  className="w-full pl-11 pr-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                />
              </div>
            </div>

            {/* Password */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label htmlFor="password" className="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                  Password
                </label>
                <button type="button" className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                  Forgot password?
                </button>
              </div>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <Lock className="w-4 h-4 text-slate-400" />
                </div>
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••••••"
                  className="w-full pl-11 pr-12 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 text-sm font-medium placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-emerald-600 transition-colors"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            {/* Remember me */}
            <div className="flex items-center gap-3">
              <input
                id="remember_me"
                type="checkbox"
                className="w-4 h-4 text-emerald-600 bg-white border-slate-300 rounded focus:ring-emerald-500/20 cursor-pointer"
              />
              <label htmlFor="remember_me" className="text-sm text-slate-500 font-medium cursor-pointer select-none">
                Keep me signed in
              </label>
            </div>

            {/* Submit */}
            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 px-6 flex items-center justify-center gap-2 text-sm font-bold text-white rounded-xl transition-all duration-300 group mt-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:scale-105 active:scale-98 disabled:opacity-50"
              style={{ boxShadow: '0 4px 24px rgba(16, 185, 129, 0.3)' }}
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" /> Authenticating...
                </>
              ) : (
                <>
                  Sign In
                  <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </>
              )}
            </button>
          </form>

          {/* Quick Demo Credentials */}
          <div className="mt-6 pt-5 border-t border-slate-200">
            <span className="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2.5">
              Quick Login Demo Accounts
            </span>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => handleQuickLogin('isinan@noun.edu.ng', 'Sinan3367#')}
                className="py-2 text-center bg-white hover:bg-slate-100 border border-slate-200 hover:border-slate-300 rounded-lg text-emerald-600 text-[10px] font-bold transition-all"
              >
                Admin (Neon Database)
              </button>
              <button
                type="button"
                onClick={() => handleQuickLogin('student@example.com')}
                className="py-2 text-center bg-white hover:bg-slate-100 border border-slate-200 hover:border-slate-300 rounded-lg text-emerald-600 text-[10px] font-bold transition-all"
              >
                Student (Quick Demo)
              </button>
            </div>
          </div>

          {/* Divider */}
          <div className="relative my-6">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-slate-200"></div>
            </div>
            <div className="relative flex justify-center">
              <span className="px-4 bg-slate-50 text-xs text-slate-400 font-medium">or</span>
            </div>
          </div>

          {/* Repository Link */}
          <a
            href="#repository"
            onClick={onNavigateToHome}
            className="w-full flex items-center justify-center gap-2 py-3.5 px-6 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:border-emerald-300 hover:text-emerald-700 transition-all"
          >
            <BookOpen className="w-4 h-4" />
            Browse Research Repository
          </a>

          {/* Back to home */}
          <p className="mt-8 text-center text-xs text-slate-400">
            <button
              onClick={onNavigateToHome}
              className="font-semibold text-slate-500 hover:text-emerald-600 transition-colors inline-flex items-center gap-1 focus:outline-none"
            >
              <ArrowLeft className="w-3 h-3" />
              Back to home
            </button>
          </p>
        </div>
      </div>
    </div>
  );
}

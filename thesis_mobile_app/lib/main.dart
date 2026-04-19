import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:ui';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:google_fonts/google_fonts.dart';

// Institutional Configuration Matrix
const String kApiBase = 'http://10.0.2.2:8000'; 
const String kLogoUrl = '$kApiBase/images/acetel-logo.jpeg';

void main() {
  runApp(const ThesisMonitoringApp());
}

class ThesisMonitoringApp extends StatelessWidget {
  const ThesisMonitoringApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Monitorsly',
      theme: ThemeData(
        textTheme: GoogleFonts.outfitTextTheme(),
        scaffoldBackgroundColor: const Color(0xFFF8FAFC),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF10B981),
          primary: const Color(0xFF0F172A),
          secondary: const Color(0xFF10B981),
          surface: Colors.white,
        ),
        useMaterial3: true,
      ),
      home: const AuthCheck(),
      debugShowCheckedModeBanner: false,
    );
  }
}

// -----------------------------------------------------------------------------
// Auth Logic
// -----------------------------------------------------------------------------

class AuthCheck extends StatefulWidget {
  const AuthCheck({super.key});

  @override
  State<AuthCheck> createState() => _AuthCheckState();
}

class _AuthCheckState extends State<AuthCheck> {
  @override
  void initState() {
    super.initState();
    _checkToken();
  }

  Future<void> _checkToken() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
    if (mounted) {
      if (token != null) {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            pageBuilder: (c, a1, a2) => const DashboardScreen(),
            transitionsBuilder: (c, a1, a2, child) => FadeTransition(opacity: a1, child: child),
          ),
        );
      } else {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            pageBuilder: (c, a1, a2) => const LoginScreen(),
            transitionsBuilder: (c, a1, a2, child) => FadeTransition(opacity: a1, child: child),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(color: Color(0xFF10B981), strokeWidth: 5),
      ),
    );
  }
}

// -----------------------------------------------------------------------------
// UI Components
// -----------------------------------------------------------------------------

class PremiumButton extends StatelessWidget {
  final String text;
  final VoidCallback? onPressed;
  final bool isLoading;

  const PremiumButton({super.key, required this.text, this.onPressed, this.isLoading = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 60,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [const Color(0xFF0F172A), const Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withOpacity(0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          )
        ],
      ),
      child: ElevatedButton(
        onPressed: isLoading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          foregroundColor: Colors.white,
          shadowColor: Colors.transparent,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        ),
        child: isLoading 
          ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
          : Text(text.toUpperCase(), style: const TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.2, fontSize: 13)),
      ),
    );
  }
}

// -----------------------------------------------------------------------------
// Login Screen
// -----------------------------------------------------------------------------

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  String? _error;

  Future<void> _login() async {
    setState(() { _isLoading = true; _error = null; });
    try {
      final response = await http.post(
        Uri.parse('$kApiBase/api/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': _emailController.text, 'password': _passwordController.text}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', data['token']);
        if (mounted) Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen()));
      } else {
        setState(() => _error = jsonDecode(response.body)['message'] ?? 'Access Denied');
      }
    } catch (e) {
      setState(() => _error = 'Institutional bridge failure. Verify server connectivity.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          Positioned(
            top: -100,
            right: -100,
            child: Container(width: 300, height: 300, decoration: BoxDecoration(color: const Color(0xFF10B981).withOpacity(0.1), shape: BoxShape.circle)),
          ),
          Positioned(
            bottom: -50,
            left: -50,
            child: Container(width: 200, height: 200, decoration: BoxDecoration(color: const Color(0xFF10B981).withOpacity(0.05), shape: BoxShape.circle)),
          ),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 40),
                  // ACETEL Logo Block
                  Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [BoxShadow(color: const Color(0xFF0F172A).withOpacity(0.08), blurRadius: 40, offset: const Offset(0, 20))],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: Image.network(
                        kLogoUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (c, e, s) => const Icon(Icons.school, color: Color(0xFF10B981), size: 40),
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                  const Text('Monitorsly', style: TextStyle(fontSize: 42, fontWeight: FontWeight.w900, color: Color(0xFF0F172A), letterSpacing: -1.5)),
                  const Text('University Portfolio Management.', style: TextStyle(fontSize: 16, color: Color(0xFF64748B), fontWeight: FontWeight.w500)),
                  const SizedBox(height: 56),
                  
                  if (_error != null)
                    Container(
                      padding: const EdgeInsets.all(16),
                      margin: const EdgeInsets.only(bottom: 32),
                      decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFFEE2E2))),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: Color(0xFFEF4444), size: 20),
                          const SizedBox(width: 12),
                          Expanded(child: Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 13, fontWeight: FontWeight.w700))),
                        ],
                      ),
                    ),

                  const Text('CREDENTIALS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: Color(0xFF94A3B8), letterSpacing: 1.5)),
                  const SizedBox(height: 12),
                  
                  TextField(
                    controller: _emailController,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                    decoration: _inputStyle('Email Address', Icons.alternate_email),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _passwordController,
                    obscureText: true,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                    decoration: _inputStyle('Access Matrix', Icons.lock_outline),
                  ),
                  const SizedBox(height: 40),
                  PremiumButton(text: 'Authenticate', onPressed: _login, isLoading: _isLoading),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  InputDecoration _inputStyle(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
      prefixIcon: Icon(icon, color: const Color(0xFF94A3B8), size: 20),
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(vertical: 20, horizontal: 20),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: Color(0xFFF1F5F9))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: Color(0xFF10B981), width: 2)),
    );
  }
}

// -----------------------------------------------------------------------------
// Dashboard Screen
// -----------------------------------------------------------------------------

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _data;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    try {
      final response = await http.get(
        Uri.parse('$kApiBase/api/thesis'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );
      if (response.statusCode == 200) {
        setState(() { _data = jsonDecode(response.body); _isLoading = false; });
      } else {
        _logout();
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    if (mounted) Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator(color: Color(0xFF10B981))));

    final thesis = _data?['thesis'];
    final milestones = thesis?['milestones'] ?? [];
    
    // Status Logic
    int daysSinceUpdate = 0;
    if (thesis != null) {
      final updated = DateTime.parse(thesis['updated_at']);
      daysSinceUpdate = DateTime.now().difference(updated).inDays;
    }
    
    String alertStatus = "ON TRACK";
    Color alertColor = const Color(0xFF10B981);
    if (daysSinceUpdate > 30) {
      alertStatus = "STALLED"; alertColor = const Color(0xFFEF4444);
    } else if (daysSinceUpdate > 14) {
      alertStatus = "DELAYED"; alertColor = const Color(0xFFF59E0B);
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 180,
            floating: false,
            pinned: true,
            backgroundColor: const Color(0xFF0F172A),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            // ACETEL Miniature
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: Image.network(kLogoUrl, fit: BoxFit.cover, errorBuilder: (c,e,s) => const Icon(Icons.school, size: 20)),
                              ),
                            ),
                            // Status & Logout
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                  decoration: BoxDecoration(color: alertColor.withOpacity(0.1), borderRadius: BorderRadius.circular(10), border: Border.all(color: alertColor.withOpacity(0.4))),
                                  child: Text(alertStatus, style: TextStyle(color: alertColor, fontWeight: FontWeight.w900, fontSize: 10, letterSpacing: 1.5)),
                                ),
                                const SizedBox(width: 8),
                                IconButton(onPressed: _logout, icon: const Icon(Icons.logout, color: Colors.white54, size: 20)),
                              ],
                            ),
                          ],
                        ),
                        const Spacer(),
                        Text(_data?['user']?['name'] ?? 'Scholar', style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w900, letterSpacing: -1)),
                        Text(_data?['program']?['name'] ?? 'Research Portal', style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 13, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
          
          SliverPadding(
            padding: const EdgeInsets.all(24),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                const Text('THESIS PROJECT', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: Color(0xFF94A3B8), letterSpacing: 1.5)),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(28), border: Border.all(color: const Color(0xFFF1F5F9)), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 40, offset: const Offset(0, 10))]),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(thesis?['title'] ?? 'Title TBD', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A), height: 1.3)),
                      const SizedBox(height: 24),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: LinearProgressIndicator(value: 0.4, minHeight: 8, backgroundColor: const Color(0xFFF1F5F9), valueColor: AlwaysStoppedAnimation<Color>(alertColor)),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Current Progress', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF64748B))),
                          Text('40%', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: alertColor)),
                        ],
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 48),
                const Text('RESEARCH PROTOCOL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: Color(0xFF94A3B8), letterSpacing: 1.5)),
                const SizedBox(height: 12),
                ...milestones.map((m) => _buildMilestoneNode(m)).toList(),
                if (milestones.isEmpty)
                   Container(
                     padding: const EdgeInsets.all(40),
                     decoration: BoxDecoration(color: const Color(0xFFF1F5F9).withOpacity(0.5), borderRadius: BorderRadius.circular(24)),
                     child: const Center(child: Text('Protocol synchronization pending...', style: TextStyle(color: Color(0xFF94A3B8), fontWeight: FontWeight.w700, fontSize: 13))),
                   ),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMilestoneNode(Map<String, dynamic> milestone) {
    final template = milestone['template'];
    final status = milestone['status'] ?? 'pending';
    
    Color nodeColor = const Color(0xFF94A3B8);
    IconData nodeIcon = Icons.circle_outlined;
    
    if (status == 'approved' || status == 'completed') {
      nodeColor = const Color(0xFF10B981); nodeIcon = Icons.check_circle;
    } else if (status == 'submitted') {
      nodeColor = const Color(0xFF3B82F6); nodeIcon = Icons.pending_actions;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: status == 'approved' ? const Color(0xFFECFDF5).withOpacity(0.5) : Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: status == 'approved' ? const Color(0xFFD1FAE5) : const Color(0xFFF1F5F9)),
      ),
      child: Row(
        children: [
          Icon(nodeIcon, color: nodeColor, size: 24),
          const SizedBox(width: 20),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('PHASE ${template?['order'] ?? "?"}', style: TextStyle(color: nodeColor, fontWeight: FontWeight.w900, fontSize: 10, letterSpacing: 1.5)),
                Text(template?['name'] ?? 'Unknown Milestone', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF0F172A))),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(color: nodeColor.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
            child: Text(status.toUpperCase(), style: TextStyle(color: nodeColor, fontWeight: FontWeight.bold, fontSize: 9)),
          ),
        ],
      ),
    );
  }
}

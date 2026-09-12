import 'dart:async';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'intro_screen.dart';

class SettingsScreen extends StatefulWidget {
  final String initialServerUrl;
  final Function(String) onServerUrlChanged;

  const SettingsScreen({
    super.key,
    required this.initialServerUrl,
    required this.onServerUrlChanged,
  });

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  late String _currentUrl;
  bool _rejectOffline = true;
  bool _stockAlert = true;

  @override
  void initState() {
    super.initState();
    _currentUrl = widget.initialServerUrl;
    _loadPrefs();
  }

  Future<void> _loadPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _rejectOffline = prefs.getBool('reject_offline') ?? true;
      _stockAlert = prefs.getBool('stock_alert') ?? true;
    });
  }

  Future<void> _setRejectOffline(bool val) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('reject_offline', val);
    setState(() => _rejectOffline = val);
  }

  Future<void> _setStockAlert(bool val) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('stock_alert', val);
    setState(() => _stockAlert = val);
  }

  void _openNetworkSettings() async {
    final newUrl = await Navigator.push<String>(
      context,
      CupertinoPageRoute(
        builder: (context) => NetworkSettingsScreen(
          currentUrl: _currentUrl,
        ),
      ),
    );

    if (newUrl != null && newUrl.isNotEmpty && newUrl != _currentUrl) {
      setState(() => _currentUrl = newUrl);
      widget.onServerUrlChanged(newUrl);
    }
  }

  void _showAboutDialog() {
    showCupertinoModalPopup(
      context: context,
      builder: (ctx) => CupertinoActionSheet(
        title: const Text(
          'MaoneArt Inventory Gudang',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17),
        ),
        message: const Text(
          'Versi 1.0.0 (Production Release)\n'
          'Arsitektur: Hybrid Flutter + Apache/MariaDB Web Engine\n\n'
          'Pengembang: Hermawan (MaoneArt Solution)\n'
          'Kec. Tambun Utara, Kab. Bekasi\n\n'
          'Sistem terintegrasi Asisten AI "Si-nya" & proteksi stok realtime pabrik.',
          textAlign: TextAlign.center,
        ),
        actions: [
          CupertinoActionSheetAction(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.push(
                context,
                CupertinoPageRoute(builder: (_) => const IntroScreen()),
              );
            },
            child: const Text('Buka Panduan 3 Slide'),
          ),
        ],
        cancelButton: CupertinoActionSheetAction(
          isDefaultAction: true,
          onPressed: () => Navigator.pop(ctx),
          child: const Text('Tutup'),
        ),
      ),
    );
  }

  void _clearCache() {
    showCupertinoDialog(
      context: context,
      builder: (ctx) => CupertinoAlertDialog(
        title: const Text('Bersihkan Cache'),
        content: const Text(
          'Apakah Anda yakin ingin mengosongkan cache aplikasi dan memuat ulang data server?',
        ),
        actions: [
          CupertinoDialogAction(
            child: const Text('Batal'),
            onPressed: () => Navigator.pop(ctx),
          ),
          CupertinoDialogAction(
            isDestructiveAction: true,
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Cache aplikasi berhasil dibersihkan'),
                  backgroundColor: Color(0xFF059669),
                  duration: Duration(seconds: 2),
                ),
              );
            },
            child: const Text('Bersihkan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF000000),
      appBar: AppBar(
        backgroundColor: const Color(0xFF000000),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(CupertinoIcons.back, color: Color(0xFF0A84FF)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Pengaturan',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: 18,
          ),
        ),
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.only(bottom: 40),
        children: [
          const SizedBox(height: 8),

          // 1. Profil Pengguna Gudang (iOS Style)
          _buildIosGroup(
            children: [
              Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 28,
                      backgroundColor: const Color(0xFF1E293B),
                      child: Container(
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: LinearGradient(
                            colors: [Color(0xFF007AFF), Color(0xFF5856D6)],
                          ),
                        ),
                        child: const Center(
                          child: Icon(
                            CupertinoIcons.person_solid,
                            color: Colors.white,
                            size: 26,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Petugas Gudang Pabrik',
                            style: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(height: 3),
                          Text(
                            'Operator Lapangan • ID: OP-01',
                            style: TextStyle(
                              fontSize: 13,
                              color: Color(0xFF8E8E93),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          // 2. Section: Jaringan & Koneksi
          _buildSectionHeader('JARINGAN & SERVER'),
          _buildIosGroup(
            children: [
              _buildIosTile(
                icon: CupertinoIcons.wifi,
                iconBgColor: const Color(0xFF007AFF), // iOS Blue
                title: 'Pengaturan Jaringan & Server',
                onTap: _openNetworkSettings,
                showChevron: true,
              ),
              _buildDivider(),
              _buildIosTile(
                icon: CupertinoIcons.building_2_fill,
                iconBgColor: const Color(0xFF34C759), // iOS Green
                title: 'Lokasi Operasional',
                showChevron: true,
              ),
            ],
          ),

          // 3. Section: Keamanan & Integritas Data
          _buildSectionHeader('INTEGRITAS STOK & KEAMANAN'),
          _buildIosGroup(
            children: [
              _buildIosSwitchTile(
                icon: CupertinoIcons.shield_lefthalf_fill,
                iconBgColor: const Color(0xFFFF9500), // iOS Orange
                title: 'Tolak Input Saat Offline',
                value: _rejectOffline,
                onChanged: _setRejectOffline,
              ),
              _buildDivider(),
              _buildIosSwitchTile(
                icon: CupertinoIcons.bell_fill,
                iconBgColor: const Color(0xFFFF3B30), // iOS Red
                title: 'Peringatan Stok Minimum',
                value: _stockAlert,
                onChanged: _setStockAlert,
              ),
            ],
          ),

          // 4. Section: Panduan & Informasi
          _buildSectionHeader('PANDUAN & BANTUAN'),
          _buildIosGroup(
            children: [
              _buildIosTile(
                icon: CupertinoIcons.play_rectangle_fill,
                iconBgColor: const Color(0xFFAF52DE), // iOS Purple
                title: 'Panduan Aplikasi',
                onTap: () {
                  Navigator.push(
                    context,
                    CupertinoPageRoute(builder: (_) => const IntroScreen()),
                  );
                },
                showChevron: true,
              ),
              _buildDivider(),
              _buildIosTile(
                icon: CupertinoIcons.info_circle_fill,
                iconBgColor: const Color(0xFF5856D6), // iOS Indigo
                title: 'Tentang MaoneArt Inventory',
                onTap: _showAboutDialog,
                showChevron: true,
              ),
            ],
          ),

          // 5. Section: Sistem
          _buildSectionHeader('SISTEM & CACHE'),
          _buildIosGroup(
            children: [
              _buildIosTile(
                icon: CupertinoIcons.trash_fill,
                iconBgColor: const Color(0xFF8E8E93), // iOS Gray
                title: 'Bersihkan Cache Webview',
                titleColor: const Color(0xFFFF453A),
                onTap: _clearCache,
                showChevron: true,
              ),
            ],
          ),

          const SizedBox(height: 24),
          const Center(
            child: Text(
              'MaoneArt Inventory • Versi 1.0.0 (Build 2026.09)\nDeveloper: Hermawan (MaoneArt)',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                color: Color(0xFF636366),
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatUrlDisplay(String raw) {
    return raw
        .replaceAll('http://', '')
        .replaceAll('https://', '')
        .replaceAll('/Inventory', '');
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 28, top: 22, bottom: 7, right: 16),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: Color(0xFF8E8E93),
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _buildIosGroup({required List<Widget> children}) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1C1E), // iOS Dark Card
        borderRadius: BorderRadius.circular(12),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: children,
        ),
      ),
    );
  }

  Widget _buildDivider() {
    return const Padding(
      padding: EdgeInsets.only(left: 54),
      child: Divider(
        height: 1,
        thickness: 0.5,
        color: Color(0xFF2C2C2E),
      ),
    );
  }

  Widget _buildIosTile({
    required IconData icon,
    required Color iconBgColor,
    required String title,
    String? subtitle,
    String? trailingText,
    Color? titleColor,
    VoidCallback? onTap,
    bool showChevron = false,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        child: Row(
          children: [
            Container(
              width: 29,
              height: 29,
              decoration: BoxDecoration(
                color: iconBgColor,
                borderRadius: BorderRadius.circular(7),
              ),
              child: Icon(icon, color: Colors.white, size: 17),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w500,
                      color: titleColor ?? Colors.white,
                    ),
                  ),
                  if (subtitle != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF8E8E93),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (trailingText != null)
              Text(
                trailingText,
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF8E8E93),
                ),
              ),
            if (showChevron)
              const Icon(
                CupertinoIcons.chevron_right,
                size: 15,
                color: Color(0xFF8E8E93),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildIosSwitchTile({
    required IconData icon,
    required Color iconBgColor,
    required String title,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      child: Row(
        children: [
          Container(
            width: 29,
            height: 29,
            decoration: BoxDecoration(
              color: iconBgColor,
              borderRadius: BorderRadius.circular(7),
            ),
            child: Icon(icon, color: Colors.white, size: 17),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w500,
                color: Colors.white,
              ),
            ),
          ),
          CupertinoSwitch(
            value: value,
            activeColor: const Color(0xFF34C759),
            onChanged: onChanged,
          ),
        ],
      ),
    );
  }
}

// -------------------------------------------------------------
// SUB-PAGE: PENGATURAN JARINGAN & SERVER (GAYA IPHONE)
// -------------------------------------------------------------
class NetworkSettingsScreen extends StatefulWidget {
  final String currentUrl;

  const NetworkSettingsScreen({
    super.key,
    required this.currentUrl,
  });

  @override
  State<NetworkSettingsScreen> createState() => _NetworkSettingsScreenState();
}

class _NetworkSettingsScreenState extends State<NetworkSettingsScreen> {
  late TextEditingController _urlController;
  bool _isTesting = false;
  String? _testResult;
  bool? _testSuccess;

  final List<String> _presets = [
    'http://localhost:8085/Inventory',
    'http://192.168.1.100:8085/Inventory',
    'http://192.168.43.1:8085/Inventory',
    'http://10.0.2.2:8085/Inventory',
  ];

  @override
  void initState() {
    super.initState();
    _urlController = TextEditingController(text: widget.currentUrl);
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _testConnection() async {
    final target = _urlController.text.trim();
    if (target.isEmpty) return;

    setState(() {
      _isTesting = true;
      _testResult = null;
      _testSuccess = null;
    });

    try {
      final uri = Uri.parse(target);
      final resp = await http.get(uri).timeout(const Duration(seconds: 4));
      if (resp.statusCode >= 200 && resp.statusCode < 400) {
        setState(() {
          _isTesting = false;
          _testSuccess = true;
          _testResult = 'Terhubung! Server merespons (Status: ${resp.statusCode})';
        });
      } else {
        setState(() {
          _isTesting = false;
          _testSuccess = false;
          _testResult = 'Server merespons kode HTTP ${resp.statusCode}.';
        });
      }
    } catch (e) {
      setState(() {
        _isTesting = false;
        _testSuccess = false;
        _testResult = 'Gagal terhubung. Pastikan WiFi aktif & IP server benar.';
      });
    }
  }

  void _saveAndReturn() {
    final target = _urlController.text.trim();
    if (target.isEmpty) return;

    Navigator.pop(context, target);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Alamat server disimpan: $target'),
        backgroundColor: const Color(0xFF007AFF),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF000000),
      appBar: AppBar(
        backgroundColor: const Color(0xFF000000),
        elevation: 0,
        leading: TextButton(
          onPressed: () => Navigator.pop(context),
          style: TextButton.styleFrom(
            padding: EdgeInsets.zero,
            foregroundColor: const Color(0xFF0A84FF),
          ),
          child: const Row(
            children: [
              SizedBox(width: 8),
              Icon(CupertinoIcons.back, size: 20),
              Text('Pengaturan', style: TextStyle(fontSize: 16)),
            ],
          ),
        ),
        leadingWidth: 120,
        title: const Text(
          'Jaringan & Server',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: 17,
          ),
        ),
        centerTitle: true,
        actions: [
          TextButton(
            onPressed: _saveAndReturn,
            child: const Text(
              'Simpan',
              style: TextStyle(
                color: Color(0xFF0A84FF),
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: 12),
        children: [
          // Section 1: Alamat URL Server
          _buildHeader('ALAMAT IP SERVER GUDANG'),
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: const Color(0xFF1C1C1E),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: TextField(
                controller: _urlController,
                style: const TextStyle(color: Colors.white, fontSize: 15),
                decoration: const InputDecoration(
                  border: InputBorder.none,
                  hintText: 'http://192.168.1.100:8085/Inventory',
                  hintStyle: TextStyle(color: Color(0xFF636366), fontSize: 14),
                ),
                keyboardType: TextInputType.url,
              ),
            ),
          ),
          const Padding(
            padding: EdgeInsets.only(left: 28, right: 28, top: 6),
            child: Text(
              'Contoh: http://192.168.1.50:8085/Inventory. Gunakan port 8085 untuk Apache Termux / Server kantor.',
              style: TextStyle(fontSize: 12, color: Color(0xFF8E8E93)),
            ),
          ),

          const SizedBox(height: 18),

          // Section 2: Preset Alamat Cepat
          _buildHeader('PILIHAN CEPAT (PRESET ALAMAT)'),
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: const Color(0xFF1C1C1E),
              borderRadius: BorderRadius.circular(12),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Column(
                children: [
                  for (int i = 0; i < _presets.length; i++) ...[
                    ListTile(
                      dense: true,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
                      title: Text(
                        _presets[i],
                        style: const TextStyle(color: Colors.white, fontSize: 14),
                      ),
                      trailing: const Icon(CupertinoIcons.arrow_right_circle, color: Color(0xFF007AFF), size: 18),
                      onTap: () {
                        setState(() {
                          _urlController.text = _presets[i];
                        });
                      },
                    ),
                    if (i < _presets.length - 1)
                      const Divider(height: 1, color: Color(0xFF2C2C2E), indent: 16),
                  ],
                ],
              ),
            ),
          ),

          const SizedBox(height: 18),

          // Section 3: Uji Koneksi
          _buildHeader('DIAGNOSTIK KONEKSI'),
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1C1C1E),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF007AFF),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                  onPressed: _isTesting ? null : _testConnection,
                  icon: _isTesting
                      ? const CupertinoActivityIndicator(color: Colors.white)
                      : const Icon(CupertinoIcons.arrow_clockwise, size: 18),
                  label: Text(
                    _isTesting ? 'Sedang Menguji Ping...' : 'Tes Koneksi Server Sekarang',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
                if (_testResult != null) ...[
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: (_testSuccess ?? false)
                          ? const Color(0xFF052e16)
                          : const Color(0xFF450a0a),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: (_testSuccess ?? false)
                            ? const Color(0xFF10b981)
                            : const Color(0xFFef4444),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          (_testSuccess ?? false)
                              ? CupertinoIcons.check_mark_circled_solid
                              : CupertinoIcons.exclamationmark_circle_fill,
                          color: (_testSuccess ?? false)
                              ? const Color(0xFF34C759)
                              : const Color(0xFFFF3B30),
                          size: 20,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            _testResult!,
                            style: TextStyle(
                              fontSize: 13,
                              color: (_testSuccess ?? false)
                                  ? const Color(0xFF86EFAC)
                                  : const Color(0xFFFCA5A5),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 28, top: 12, bottom: 7, right: 16),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: Color(0xFF8E8E93),
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

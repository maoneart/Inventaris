import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'webview_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String _serverUrl = 'http://192.168.1.100:8085/Inventaris';
  static const String _prefKey = 'server_base_url';

  @override
  void initState() {
    super.initState();
    _loadSavedServerUrl();
  }

  Future<void> _loadSavedServerUrl() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _serverUrl = prefs.getString(_prefKey) ?? 'http://192.168.1.100:8085/Inventaris';
    });
  }

  Future<void> _saveServerUrl(String newUrl) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefKey, newUrl.trim());
    setState(() {
      _serverUrl = newUrl.trim();
    });
  }

  void _showConfigDialog() {
    final controller = TextEditingController(text: _serverUrl);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Pengaturan Server Kantor', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Masukkan alamat IP Komputer Server di WiFi kantor:',
              style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: InputDecoration(
                hintText: 'http://192.168.x.x:8085/Inventaris',
                hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                filled: true,
                fillColor: const Color(0xFF0F172A),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              ),
              style: const TextStyle(fontSize: 13),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF94A3B8))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2563EB)),
            onPressed: () {
              if (controller.text.isNotEmpty) {
                _saveServerUrl(controller.text);
              }
              Navigator.pop(ctx);
            },
            child: const Text('Simpan', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _openPage(String path, String title) {
    String cleanBase = _serverUrl.endsWith('/') ? _serverUrl.substring(0, _serverUrl.length - 1) : _serverUrl;
    String targetUrl = '$cleanBase/$path';
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => WebViewScreen(url: targetUrl, title: title),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F172A),
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'MaoneArt Gudang',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
            ),
            Row(
              children: [
                Container(
                  width: 6,
                  height: 6,
                  decoration: const BoxDecoration(color: Color(0xFF10B981), shape: BoxShape.circle),
                ),
                const SizedBox(width: 5),
                Expanded(
                  child: Text(
                    _serverUrl,
                    style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined, color: Color(0xFF94A3B8)),
            onPressed: _showConfigDialog,
            tooltip: 'Atur IP Server Kantor',
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Banner Minimalis
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: const Color(0xFF2563EB).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.inventory_2_outlined, color: Color(0xFF60A5FA), size: 24),
                    ),
                    const SizedBox(width: 14),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Sistem Stok Realtime',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Petugas lapangan & penerimaan logistik',
                            style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // 2 Tombol Utama Simetris (Barang Masuk & Keluar)
              Row(
                children: [
                  Expanded(
                    child: _buildActionCard(
                      title: 'Barang Masuk',
                      subtitle: 'Dari Supplier',
                      icon: Icons.south_west_rounded,
                      color: const Color(0xFF10B981),
                      onTap: () => _openPage('masuk.php', 'Input Barang Masuk'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildActionCard(
                      title: 'Barang Keluar',
                      subtitle: 'Ke Teknisi / PIC',
                      icon: Icons.north_east_rounded,
                      color: const Color(0xFFEF4444),
                      onTap: () => _openPage('keluar.php', 'Input Barang Keluar'),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 24),

              // Menu Cepat Minimalis
              const Text(
                'MENU UTAMA',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B), letterSpacing: 0.8),
              ),
              const SizedBox(height: 10),

              _buildListMenuTile(
                title: 'Aktual Stok Realtime',
                subtitle: 'Pantau sisa fisik barang & part number',
                icon: Icons.grid_view_rounded,
                color: const Color(0xFF3B82F6),
                onTap: () => _openPage('index.php', 'Aktual Stok Realtime'),
              ),
              _buildListMenuTile(
                title: 'Data Barang & Part Number',
                subtitle: 'Katalog SKU, P/N, & asal supplier',
                icon: Icons.layers_outlined,
                color: const Color(0xFF8B5CF6),
                onTap: () => _openPage('barang.php', 'Data Barang & P/N'),
              ),
              _buildListMenuTile(
                title: 'Data Rekanan Supplier',
                subtitle: 'Daftar vendor penyedia material',
                icon: Icons.local_shipping_outlined,
                color: const Color(0xFFF59E0B),
                onTap: () => _openPage('supplier.php', 'Data Supplier'),
              ),
              _buildListMenuTile(
                title: 'Data PIC Peminta Tools',
                subtitle: 'Teknisi dan penanggung jawab divisi',
                icon: Icons.people_outline,
                color: const Color(0xFF06B6D4),
                onTap: () => _openPage('pic.php', 'Data PIC'),
              ),
              _buildListMenuTile(
                title: 'Tanya Si-nya (AI Assistant)',
                subtitle: 'Tanya stok natural & minta draf laporan',
                icon: Icons.smart_toy_outlined,
                color: const Color(0xFFA855F7),
                onTap: () => _openPage('tanya_ai.php', 'Tanya Si-nya (AI)'),
              ),
              _buildListMenuTile(
                title: 'Rekapitulasi Laporan',
                subtitle: 'Filter periode & ekspor Excel/PDF',
                icon: Icons.description_outlined,
                color: const Color(0xFF64748B),
                onTap: () => _openPage('laporan.php', 'Laporan Mutasi'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActionCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: color.withOpacity(0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(height: 14),
            Text(
              title,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const SizedBox(height: 2),
            Text(
              subtitle,
              style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildListMenuTile({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: const Color(0xFF161F30),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFF1E293B)),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 18),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Colors.white)),
                    Text(subtitle, style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: Color(0xFF475569), size: 18),
            ],
          ),
        ),
      ),
    );
  }
}

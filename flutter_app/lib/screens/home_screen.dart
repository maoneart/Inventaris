import 'dart:async';
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

  late final PageController _pageController;
  int _currentBannerIndex = 0;
  Timer? _bannerTimer;

  final List<Map<String, dynamic>> _banners = [
    {
      'title': 'Penerimaan Barang Masuk',
      'desc': 'Input kiriman supplier & surat jalan lebih cepat dan tercatat rapi.',
      'gradient': [const Color(0xFF059669), const Color(0xFF10B981)],
      'icon': Icons.move_to_inbox_rounded,
      'badge': 'STOCK IN',
      'target': 'masuk.php',
      'targetTitle': 'Input Barang Masuk',
    },
    {
      'title': 'Pengeluaran Tools & Material',
      'desc': 'Catat pengambilan oleh teknisi / PIC dengan proteksi sisa stok fisik.',
      'gradient': [const Color(0xFFDC2626), const Color(0xFFEF4444)],
      'icon': Icons.outbox_rounded,
      'badge': 'STOCK OUT',
      'target': 'keluar.php',
      'targetTitle': 'Input Barang Keluar',
    },
    {
      'title': 'Si-nya: Asisten AI Gudang',
      'desc': 'Tanya sisa stok & minta draf laporan langsung dari ponsel Anda.',
      'gradient': [const Color(0xFF7C3AED), const Color(0xFF8B5CF6)],
      'icon': Icons.smart_toy_rounded,
      'badge': 'AI ASSISTANT',
      'target': 'tanya_ai.php',
      'targetTitle': 'Tanya Si-nya (AI)',
    },
    {
      'title': 'Cetak PDF & Ekspor Excel',
      'desc': 'Dokumen rekapitulasi mutasi resmi siap ditandatangani supervisor.',
      'gradient': [const Color(0xFF1D4ED8), const Color(0xFF2563EB)],
      'icon': Icons.description_rounded,
      'badge': 'LAPORAN',
      'target': 'laporan.php',
      'targetTitle': 'Laporan Mutasi',
    },
  ];

  @override
  void initState() {
    super.initState();
    _loadSavedServerUrl();
    _pageController = PageController(initialPage: 0);

    // Auto slide carousel ala Gojek setiap 4 detik
    _bannerTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (_pageController.hasClients) {
        int next = (_currentBannerIndex + 1) % _banners.length;
        _pageController.animateToPage(
          next,
          duration: const Duration(milliseconds: 400),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  @override
  void dispose() {
    _bannerTimer?.cancel();
    _pageController.dispose();
    super.dispose();
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
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.wifi_tethering, color: Color(0xFF38BDF8), size: 20),
            SizedBox(width: 8),
            Text('IP Server Kantor', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Masukkan alamat IP Server Komputer Kantor di jaringan WiFi:',
              style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: InputDecoration(
                hintText: 'http://192.168.1.50:8085/Inventaris',
                hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                filled: true,
                fillColor: const Color(0xFF0F172A),
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              ),
              style: const TextStyle(fontSize: 13, color: Colors.white),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal', style: TextStyle(color: Color(0xFF94A3B8))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () {
              if (controller.text.isNotEmpty) {
                _saveServerUrl(controller.text);
              }
              Navigator.pop(ctx);
            },
            child: const Text('Simpan & Konek', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // 1. Top Bar Ala Gojek (Search & Server Info)
              _buildGojekTopBar(),

              const SizedBox(height: 16),

              // 2. Iklan Slider / Carousel Banner
              _buildCarouselSlider(),

              const SizedBox(height: 10),

              // Indikator Titik (Dots) Banner
              _buildDotsIndicator(),

              const SizedBox(height: 20),

              // 3. Grid Tombol Menu Utama Ala Gojek (4 Kolom Bulat/Rounded)
              _buildGojekIconGrid(),

              const SizedBox(height: 24),

              // 4. Kartu Ringkasan Cepat Ala Gopay
              _buildGopayStyleQuickStats(),

              const SizedBox(height: 20),

              // 5. Section Menu Pintasan Tambahan
              _buildQuickSection(),

              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  // Top Bar Ala Gojek
  Widget _buildGojekTopBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        border: Border(bottom: BorderSide(color: Color(0xFF1E293B))),
      ),
      child: Row(
        children: [
          // Logo Icon
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF2563EB), Color(0xFF8B5CF6)]),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.inventory_2, color: Colors.white, size: 20),
          ),
          const SizedBox(width: 12),

          // Search Box Fake yang langsung buka webbase / filter
          Expanded(
            child: InkWell(
              onTap: () => _openPage('index.php', 'Cari Barang & Stok'),
              borderRadius: BorderRadius.circular(20),
              child: Container(
                height: 38,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.search, color: Color(0xFF94A3B8), size: 18),
                    SizedBox(width: 8),
                    Text(
                      'Cari barang / P/N / rak...',
                      style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),

          // Tombol Pengaturan Server IP
          IconButton(
            icon: Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: const Icon(Icons.tune_rounded, color: Color(0xFF60A5FA), size: 18),
            ),
            onPressed: _showConfigDialog,
            tooltip: 'Atur IP Server Kantor',
          ),
        ],
      ),
    );
  }

  // Banner Carousel Slider Ala Iklan Gojek
  Widget _buildCarouselSlider() {
    return SizedBox(
      height: 155,
      child: PageView.builder(
        controller: _pageController,
        itemCount: _banners.length,
        onPageChanged: (index) {
          setState(() {
            _currentBannerIndex = index;
          });
        },
        itemBuilder: (context, index) {
          final item = _banners[index];
          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: InkWell(
              onTap: () => _openPage(item['target'], item['targetTitle']),
              borderRadius: BorderRadius.circular(18),
              child: Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: item['gradient'] as List<Color>,
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(
                      color: (item['gradient'][0] as Color).withOpacity(0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.black.withOpacity(0.25),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              item['badge'],
                              style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: 0.5),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            item['title'],
                            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 4),
                          Text(
                            item['desc'],
                            style: TextStyle(fontSize: 11, color: Colors.white.withOpacity(0.9), height: 1.3),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(item['icon'] as IconData, color: Colors.white, size: 28),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  // Dots Indikator Banner
  Widget _buildDotsIndicator() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(
        _banners.length,
        (index) => AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          margin: const EdgeInsets.symmetric(horizontal: 3),
          width: _currentBannerIndex == index ? 20 : 6,
          height: 6,
          decoration: BoxDecoration(
            color: _currentBannerIndex == index ? const Color(0xFF60A5FA) : const Color(0xFF334155),
            borderRadius: BorderRadius.circular(3),
          ),
        ),
      ),
    );
  }

  // Grid Tombol Menu Ala Gojek (4 Kolom Ikon Bersih Tanpa Slide Kiri)
  Widget _buildGojekIconGrid() {
    final menuItems = [
      {
        'title': 'Brg Masuk',
        'sub': 'Stock In',
        'icon': Icons.add_box_rounded,
        'bg': const Color(0xFF10B981),
        'target': 'masuk.php',
        'targetTitle': 'Input Barang Masuk',
      },
      {
        'title': 'Brg Keluar',
        'sub': 'Stock Out',
        'icon': Icons.indeterminate_check_box_rounded,
        'bg': const Color(0xFFEF4444),
        'target': 'keluar.php',
        'targetTitle': 'Input Barang Keluar',
      },
      {
        'title': 'Data Barang',
        'sub': 'Katalog & P/N',
        'icon': Icons.inventory_2_rounded,
        'bg': const Color(0xFF3B82F6),
        'target': 'barang.php',
        'targetTitle': 'Data Barang & Part Number',
      },
      {
        'title': 'Supplier',
        'sub': 'Rekanan Vendor',
        'icon': Icons.local_shipping_rounded,
        'bg': const Color(0xFFF59E0B),
        'target': 'supplier.php',
        'targetTitle': 'Data Supplier',
      },
      {
        'title': 'Data PIC',
        'sub': 'Peminta Tools',
        'icon': Icons.engineering_rounded,
        'bg': const Color(0xFF06B6D4),
        'target': 'pic.php',
        'targetTitle': 'Data PIC Peminta',
      },
      {
        'title': 'Tanya AI',
        'sub': 'Si-nya Asisten',
        'icon': Icons.smart_toy_rounded,
        'bg': const Color(0xFFA855F7),
        'target': 'tanya_ai.php',
        'targetTitle': 'Tanya Si-nya (AI)',
      },
      {
        'title': 'Laporan',
        'sub': 'Arus Mutasi',
        'icon': Icons.bar_chart_rounded,
        'bg': const Color(0xFF64748B),
        'target': 'laporan.php',
        'targetTitle': 'Laporan Mutasi',
      },
      {
        'title': 'Web Portal',
        'sub': 'Full Dashboard',
        'icon': Icons.desktop_windows_rounded,
        'bg': const Color(0xFF4F46E5),
        'target': 'index.php',
        'targetTitle': 'Web Dashboard Gudang',
      },
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'MENU LAYANAN GUDANG',
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.8),
          ),
          const SizedBox(height: 14),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: menuItems.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              mainAxisSpacing: 16,
              crossAxisSpacing: 10,
              childAspectRatio: 0.82,
            ),
            itemBuilder: (context, index) {
              final item = menuItems[index];
              return InkWell(
                onTap: () => _openPage(item['target'] as String, item['targetTitle'] as String),
                borderRadius: BorderRadius.circular(14),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 50,
                      height: 50,
                      decoration: BoxDecoration(
                        color: (item['bg'] as Color).withOpacity(0.15),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: (item['bg'] as Color).withOpacity(0.35), width: 1.2),
                      ),
                      child: Icon(item['icon'] as IconData, color: item['bg'] as Color, size: 24),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item['title'] as String,
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      item['sub'] as String,
                      style: const TextStyle(fontSize: 9, color: Color(0xFF94A3B8)),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // Kartu Ringkasan Cepat Ala Gopay
  Widget _buildGopayStyleQuickStats() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF161F30),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFF1E293B)),
        ),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.hub_outlined, color: Color(0xFF60A5FA), size: 16),
                    SizedBox(width: 6),
                    Text('Status Server Gudang', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF10B981).withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                  ),
                  child: const Text('TERHUBUNG', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Color(0xFF34D399))),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(height: 1, color: const Color(0xFF1E293B)),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildMiniStatCol(
                    label: 'Stok Terdaftar',
                    value: 'Realtime',
                    icon: Icons.check_circle_outline,
                    color: const Color(0xFF38BDF8),
                    onTap: () => _openPage('index.php', 'Stok Realtime'),
                  ),
                ),
                Container(width: 1, height: 32, color: const Color(0xFF1E293B)),
                Expanded(
                  child: _buildMiniStatCol(
                    label: 'Stok Kritis',
                    value: 'Periksa',
                    icon: Icons.warning_amber_rounded,
                    color: const Color(0xFFF87171),
                    onTap: () => _openPage('tanya_ai.php', 'Cek Stok Kritis'),
                  ),
                ),
                Container(width: 1, height: 32, color: const Color(0xFF1E293B)),
                Expanded(
                  child: _buildMiniStatCol(
                    label: 'Cetak Dokumen',
                    value: 'Excel / PDF',
                    icon: Icons.print_outlined,
                    color: const Color(0xFFFBBF24),
                    onTap: () => _openPage('laporan.php', 'Cetak Laporan'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMiniStatCol({
    required String label,
    required String value,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
          Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
        ],
      ),
    );
  }

  // Section Banner Akses Cepat Bawah
  Widget _buildQuickSection() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: InkWell(
        onTap: () => _openPage('tanya_ai.php', 'Tanya Si-nya (AI)'),
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF1E1035), Color(0xFF1E293B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFF8B5CF6).withOpacity(0.3)),
          ),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFF8B5CF6).withOpacity(0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.auto_awesome, color: Color(0xFFC084FC), size: 22),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Butuh Rekap Cepat?', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white)),
                    SizedBox(height: 2),
                    Text('Tanya Si-nya AI untuk minta ringkasan barang masuk/keluar', style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8))),
                  ],
                ),
              ),
              const Icon(Icons.arrow_forward_ios_rounded, color: Color(0xFF8B5CF6), size: 14),
            ],
          ),
        ),
      ),
    );
  }
}

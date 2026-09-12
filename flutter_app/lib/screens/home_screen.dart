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
      'title': 'Penerimaan Kiriman',
      'desc': 'Catat no surat jalan & multi-item supplier cepat.',
      'gradient': [const Color(0xFF00AA13), const Color(0xFF059669)],
      'icon': Icons.move_to_inbox_rounded,
      'badge': 'STOCK IN',
      'target': 'masuk.php',
      'targetTitle': 'Input Barang Masuk',
    },
    {
      'title': 'Pengeluaran Tools & Part',
      'desc': 'Otomatis mengurangi sisa stok fisik di gudang.',
      'gradient': [const Color(0xFFEE2737), const Color(0xFFB91C1C)],
      'icon': Icons.outbox_rounded,
      'badge': 'STOCK OUT',
      'target': 'keluar.php',
      'targetTitle': 'Input Barang Keluar',
    },
    {
      'title': 'Si-nya: Asisten AI Gudang',
      'desc': 'Tanya sisa stok & minta draf laporan langsung.',
      'gradient': [const Color(0xFF7C3AED), const Color(0xFF4F46E5)],
      'icon': Icons.smart_toy_rounded,
      'badge': 'AI ASSISTANT',
      'target': 'tanya_ai.php',
      'targetTitle': 'Tanya Si-nya (AI)',
    },
    {
      'title': 'Cetak Dokumen Resmi',
      'desc': 'Format Excel & PDF siap ditandatangani supervisor.',
      'gradient': [const Color(0xFF0284C7), const Color(0xFF0369A1)],
      'icon': Icons.print_rounded,
      'badge': 'DOKUMEN',
      'target': 'laporan.php',
      'targetTitle': 'Laporan Mutasi',
    },
  ];

  @override
  void initState() {
    super.initState();
    _loadSavedServerUrl();
    _pageController = PageController(initialPage: 0);

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
            Icon(Icons.wifi_tethering, color: Color(0xFF00AA13), size: 20),
            SizedBox(width: 8),
            Text('IP Server Kantor', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Masukkan alamat IP Server Komputer Kantor:',
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
              backgroundColor: const Color(0xFF00AA13),
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
              // 1. Top Bar Ala Gojek
              _buildGojekTopBar(),

              const SizedBox(height: 14),

              // 2. GoPay Style Wallet Card (Stok & Aksi Cepat)
              _buildGopayCard(),

              const SizedBox(height: 18),

              // 3. Grid 8 Tombol Ikon Layanan Ala Gojek
              _buildGojekServicesGrid(),

              const SizedBox(height: 20),

              // 4. Iklan Slider / Carousel Promo Ala Gojek
              _buildCarouselSlider(),

              const SizedBox(height: 10),

              // Dots Indikator
              _buildDotsIndicator(),

              const SizedBox(height: 22),

              // 5. Feed Info Gudang Bawah
              _buildRecentFeedCard(),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
      bottomNavigationBar: _buildGojekBottomNav(),
    );
  }

  // 1. Top Bar Ala Gojek
  Widget _buildGojekTopBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        border: Border(bottom: BorderSide(color: Color(0xFF1E293B))),
      ),
      child: Row(
        children: [
          Expanded(
            child: InkWell(
              onTap: () => _openPage('index.php', 'Cari Barang & Stok'),
              borderRadius: BorderRadius.circular(20),
              child: Container(
                height: 40,
                padding: const EdgeInsets.symmetric(horizontal: 14),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFF334155)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.search, color: Color(0xFF94A3B8), size: 18),
                    SizedBox(width: 10),
                    Text(
                      'Cari part number, barang, supplier...',
                      style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          InkWell(
            onTap: _showConfigDialog,
            borderRadius: BorderRadius.circular(20),
            child: Container(
              width: 40,
              height: 40,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(colors: [Color(0xFF00AA13), Color(0xFF10B981)]),
              ),
              child: const Icon(Icons.person, color: Colors.white, size: 20),
            ),
          ),
        ],
      ),
    );
  }

  // 2. GoPay Wallet Card Style
  Widget _buildGopayCard() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: const Color(0xFF162033),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFF1E293B)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.3),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            // Saldo Kiri
            Container(
              padding: const EdgeInsets.only(right: 14),
              decoration: const BoxDecoration(
                border: Border(right: BorderSide(color: Color(0xFF1E293B), width: 1.5)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.inventory_2_rounded, color: Color(0xFF60A5FA), size: 12),
                      SizedBox(width: 4),
                      Text(
                        'STOK GUDANG',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF60A5FA), letterSpacing: 0.5),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  const Text(
                    'Aktual Realtime',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Container(
                        width: 6,
                        height: 6,
                        decoration: const BoxDecoration(color: Color(0xFF00AA13), shape: BoxShape.circle),
                      ),
                      const SizedBox(width: 4),
                      const Text(
                        'Server Terhubung',
                        style: TextStyle(fontSize: 10, color: Color(0xFF34D399), fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // 4 Aksi Cepat Kanan
            Expanded(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildGopayMiniBtn(
                    label: 'Masuk',
                    icon: Icons.south_west_rounded,
                    color: const Color(0xFF00AA13),
                    onTap: () => _openPage('masuk.php', 'Input Barang Masuk'),
                  ),
                  _buildGopayMiniBtn(
                    label: 'Keluar',
                    icon: Icons.north_east_rounded,
                    color: const Color(0xFFEE2737),
                    onTap: () => _openPage('keluar.php', 'Input Barang Keluar'),
                  ),
                  _buildGopayMiniBtn(
                    label: 'Tanya AI',
                    icon: Icons.smart_toy_rounded,
                    color: const Color(0xFF8B5CF6),
                    onTap: () => _openPage('tanya_ai.php', 'Tanya Si-nya (AI)'),
                  ),
                  _buildGopayMiniBtn(
                    label: 'Cetak',
                    icon: Icons.print_rounded,
                    color: const Color(0xFFFBBF24),
                    onTap: () => _openPage('export.php?type=stok_pdf', 'Cetak Dokumen'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGopayMiniBtn({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: color.withOpacity(0.18),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: color.withOpacity(0.35)),
            ),
            child: Icon(icon, color: color, size: 17),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFFCBD5E1)),
          ),
        ],
      ),
    );
  }

  // 3. Grid 8 Tombol Layanan Ala Gojek
  Widget _buildGojekServicesGrid() {
    final services = [
      {
        'title': 'Brg Masuk',
        'icon': Icons.input_rounded,
        'color': const Color(0xFF00AA13), // Gojek Green
        'target': 'masuk.php',
        'targetTitle': 'Input Barang Masuk',
      },
      {
        'title': 'Brg Keluar',
        'icon': Icons.output_rounded,
        'color': const Color(0xFFEE2737), // Gojek Red
        'target': 'keluar.php',
        'targetTitle': 'Input Barang Keluar',
      },
      {
        'title': 'Katalog P/N',
        'icon': Icons.layers_rounded,
        'color': const Color(0xFF0081A0), // Gojek Blue
        'target': 'barang.php',
        'targetTitle': 'Data Barang & Part Number',
      },
      {
        'title': 'Supplier',
        'icon': Icons.local_shipping_rounded,
        'color': const Color(0xFFDF6B00), // Gojek Orange
        'target': 'supplier.php',
        'targetTitle': 'Data Supplier',
      },
      {
        'title': 'Data PIC',
        'icon': Icons.people_alt_rounded,
        'color': const Color(0xFF00A3A6), // Teal
        'target': 'pic.php',
        'targetTitle': 'Data PIC Peminta',
      },
      {
        'title': 'Tanya AI',
        'icon': Icons.smart_toy_rounded,
        'color': const Color(0xFF8B5CF6), // Purple
        'target': 'tanya_ai.php',
        'targetTitle': 'Tanya Si-nya (AI)',
      },
      {
        'title': 'Laporan',
        'icon': Icons.insert_chart_rounded,
        'color': const Color(0xFF475569), // Slate
        'target': 'laporan.php',
        'targetTitle': 'Laporan Mutasi',
      },
      {
        'title': 'Lainnya',
        'icon': Icons.grid_view_rounded,
        'color': const Color(0xFF1E293B), // Navy
        'target': 'index.php',
        'targetTitle': 'Web Dashboard Gudang',
      },
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: services.length,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 4,
          mainAxisSpacing: 16,
          crossAxisSpacing: 10,
          childAspectRatio: 0.9,
        ),
        itemBuilder: (context, index) {
          final item = services[index];
          final color = item['color'] as Color;
          final isDark = color.value == 0xFF1E293B;

          return InkWell(
            onTap: () => _openPage(item['target'] as String, item['targetTitle'] as String),
            borderRadius: BorderRadius.circular(18),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: color,
                    borderRadius: BorderRadius.circular(18),
                    border: isDark ? Border.all(color: Colors.white.withOpacity(0.15)) : null,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.25),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Icon(
                    item['icon'] as IconData,
                    color: isDark ? const Color(0xFF60A5FA) : Colors.white,
                    size: 24,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  item['title'] as String,
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFFF1F5F9)),
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  // 4. Carousel Slider Iklan Ala Gojek Promo
  Widget _buildCarouselSlider() {
    return SizedBox(
      height: 135,
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
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: item['gradient'] as List<Color>,
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: (item['gradient'][0] as Color).withOpacity(0.35),
                      blurRadius: 14,
                      offset: const Offset(0, 5),
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
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.black.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              item['badge'],
                              style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Colors.white),
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            item['title'],
                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.white),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 3),
                          Text(
                            item['desc'],
                            style: TextStyle(fontSize: 10.5, color: Colors.white.withOpacity(0.9)),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    Icon(item['icon'] as IconData, color: Colors.white.withOpacity(0.9), size: 36),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  // Dots Indikator
  Widget _buildDotsIndicator() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(
        _banners.length,
        (index) => AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          margin: const EdgeInsets.symmetric(horizontal: 3),
          width: _currentBannerIndex == index ? 18 : 6,
          height: 6,
          decoration: BoxDecoration(
            color: _currentBannerIndex == index ? const Color(0xFF00AA13) : const Color(0xFF334155),
            borderRadius: BorderRadius.circular(3),
          ),
        ),
      ),
    );
  }

  // 5. Feed Info Gudang Bawah
  Widget _buildRecentFeedCard() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF162033),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFF1E293B)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Aktivitas Terkini Gudang',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                InkWell(
                  onTap: () => _openPage('laporan.php', 'Riwayat Laporan'),
                  child: const Text(
                    'Lihat Semua',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF00AA13)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _buildFeedRow(
              title: 'Penerimaan Part dari PT Mandiri',
              sub: 'SJ-889 • Baut & Kawat Las',
              time: 'Hari ini',
              isIn: true,
            ),
            const Divider(color: Color(0xFF1E293B), height: 16),
            _buildFeedRow(
              title: 'Pengambilan Tools oleh Budi S.',
              sub: 'Maintenance Line 2 • Gerinda Tangan',
              time: 'Kemarin',
              isIn: false,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFeedRow({
    required String title,
    required String sub,
    required String time,
    required bool isIn,
  }) {
    return Row(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: (isIn ? const Color(0xFF00AA13) : const Color(0xFFEE2737)).withOpacity(0.18),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(
            isIn ? Icons.move_to_inbox_rounded : Icons.outbox_rounded,
            color: isIn ? const Color(0xFF00AA13) : const Color(0xFFEE2737),
            size: 16,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white)),
              Text(sub, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8))),
            ],
          ),
        ),
        Text(time, style: const TextStyle(fontSize: 10, color: Color(0xFF64748B))),
      ],
    );
  }

  // 6. Bottom Navigation Bar Ala Gojek
  Widget _buildGojekBottomNav() {
    return Container(
      height: 60,
      decoration: const BoxDecoration(
        color: Color(0xFF0F172A),
        border: Border(top: BorderSide(color: Color(0xFF1E293B))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildNavTabItem(icon: Icons.home_filled, label: 'Beranda', active: true, onTap: () {}),
          _buildNavTabItem(icon: Icons.input_rounded, label: 'Masuk', active: false, onTap: () => _openPage('masuk.php', 'Input Masuk')),
          _buildNavTabItem(icon: Icons.smart_toy_rounded, label: 'Tanya AI', active: false, onTap: () => _openPage('tanya_ai.php', 'Tanya AI')),
          _buildNavTabItem(icon: Icons.output_rounded, label: 'Keluar', active: false, onTap: () => _openPage('keluar.php', 'Input Keluar')),
          _buildNavTabItem(icon: Icons.grid_view_rounded, label: 'Web', active: false, onTap: () => _openPage('index.php', 'Web Portal')),
        ],
      ),
    );
  }

  Widget _buildNavTabItem({
    required IconData icon,
    required String label,
    required bool active,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: active ? const Color(0xFF00AA13) : const Color(0xFF64748B), size: 22),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              color: active ? const Color(0xFF00AA13) : const Color(0xFF64748B),
            ),
          ),
        ],
      ),
    );
  }
}

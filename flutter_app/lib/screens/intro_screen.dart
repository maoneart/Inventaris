import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'home_screen.dart';

class IntroScreen extends StatefulWidget {
  final bool isReplay;
  const IntroScreen({super.key, this.isReplay = false});

  @override
  State<IntroScreen> createState() => _IntroScreenState();
}

class _IntroScreenState extends State<IntroScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  final List<Map<String, dynamic>> _slides = [
    {
      'badge': 'SLIDE 1 / 3 • INVENTORY PABRIK',
      'title': 'Manajemen Stok Gudang',
      'subtitle': 'Pencatatan Presisi & Terintegrasi',
      'desc':
          'Aplikasi cerdas untuk memantau seluruh arus keluar-masuk barang, part number mesin, dan tools kerja pabrik secara real-time dengan sinkronisasi database MariaDB.',
      'icon': Icons.inventory_2_rounded,
      'gradient': [const Color(0xFF2563EB), const Color(0xFF06B6D4)],
      'highlights': [
        'Input Barang Masuk (Stock In) & Barang Keluar (Stock Out) instan',
        'Monitoring batas stok minimum untuk mencegah barang habis',
        'Cetak dokumen rekapitulasi mutasi format Excel & PDF resmi'
      ],
    },
    {
      'badge': 'SLIDE 2 / 3 • FITUR UNGGULAN',
      'title': 'Live Search & Kontrol Stok',
      'subtitle': 'Bebas Pilih & Proteksi Stok Minus',
      'desc':
          'Temukan barang dalam hitungan detik dengan autocomplete cerdas berdasarkan Nama, Part Number, Kode, atau Lokasi Rak.',
      'icon': Icons.filter_alt_rounded,
      'gradient': [const Color(0xFF10B981), const Color(0xFF059669)],
      'highlights': [
        'Filter otomatis khusus barang supplier pengirim saat barang masuk',
        'PIC bebas ambil barang dengan info stok fisik awal yang jelas',
        'Proteksi ketat: cegah transaksi jika kuantitas melebihi sisa stok fisik'
      ],
    },
    {
      'badge': 'SLIDE 3 / 3 • ABOUT & CREATOR',
      'title': 'Tentang Aplikasi',
      'subtitle': 'MaoneArt Digital Engineering',
      'desc':
          'Dikembangkan khusus oleh Hermawan (MaoneArt) untuk mempermudah operasional industri pabrik. Terintegrasi penuh dengan Asisten Cerdas AI.',
      'icon': Icons.smart_toy_rounded,
      'gradient': [const Color(0xFF8B5CF6), const Color(0xFFEC4899)],
      'highlights': [
        'Didukung AI Assistant "Si-nya" via 9Router Gateway lokal',
        'Mode Fleksibel: Server HP Termux (127.0.0.1) & Server LAN Kantor',
        'Desain minimalis modern standar iOS Glassmorphism yang responsif'
      ],
    },
  ];

  Future<void> _finishIntro() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('has_seen_intro', true);

    if (!mounted) return;

    if (widget.isReplay) {
      Navigator.pop(context);
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const HomeScreen()),
      );
    }
  }

  void _nextPage() {
    if (_currentPage < _slides.length - 1) {
      _pageController.nextPage(
        duration: const Duration(milliseconds: 350),
        curve: Curves.easeInOutCubic,
      );
    } else {
      _finishIntro();
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      body: SafeArea(
        child: Column(
          children: [
            // Top Bar dengan Tombol Lewati / Tutup
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFF334155)),
                        ),
                        child: const Icon(
                          Icons.box_rounded,
                          color: Color(0xFF38BDF8),
                          size: 18,
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Text(
                        'MaoneArt Inventory',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: -0.2,
                        ),
                      ),
                    ],
                  ),
                  TextButton(
                    onPressed: _finishIntro,
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFF94A3B8),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    ),
                    child: Text(
                      widget.isReplay ? 'Tutup' : 'Lewati',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  ),
                ],
              ),
            ),

            // Page View Slider 3 Slide
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                itemCount: _slides.length,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemBuilder: (context, index) {
                  final slide = _slides[index];
                  final List<Color> grad = slide['gradient'];
                  final List<String> highlights = slide['highlights'];

                  return SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        const SizedBox(height: 16),

                        // Ilustrasi Icon Glowing
                        Stack(
                          alignment: Alignment.center,
                          children: [
                            Container(
                              width: 140,
                              height: 140,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: grad[0].withOpacity(0.18),
                              ),
                            ),
                            Container(
                              width: 104,
                              height: 104,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: LinearGradient(
                                  colors: grad,
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: grad[0].withOpacity(0.45),
                                    blurRadius: 24,
                                    offset: const Offset(0, 8),
                                  ),
                                ],
                              ),
                              child: Icon(
                                slide['icon'] as IconData,
                                size: 52,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 24),

                        // Badge Kategori
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            color: grad[0].withOpacity(0.15),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: grad[0].withOpacity(0.35)),
                          ),
                          child: Text(
                            slide['badge'] as String,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: grad[0],
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),

                        const SizedBox(height: 12),

                        // Judul Slide
                        Text(
                          slide['title'] as String,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                            letterSpacing: -0.5,
                          ),
                        ),

                        const SizedBox(height: 4),

                        // Subtitle
                        Text(
                          slide['subtitle'] as String,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: grad[0],
                          ),
                        ),

                        const SizedBox(height: 12),

                        // Deskripsi
                        Text(
                          slide['desc'] as String,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 13,
                            height: 1.5,
                            color: Color(0xFF94A3B8),
                          ),
                        ),

                        const SizedBox(height: 20),

                        // Box Poin-poin Keunggulan
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: const Color(0xFF162033),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFF1E293B)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: highlights.map((text) {
                              return Padding(
                                padding: const EdgeInsets.symmetric(vertical: 4),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Icon(
                                      Icons.check_circle_rounded,
                                      size: 16,
                                      color: grad[0],
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Text(
                                        text,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: Color(0xFFE2E8F0),
                                          height: 1.4,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
                          ),
                        ),

                        const SizedBox(height: 16),
                      ],
                    ),
                  );
                },
              ),
            ),

            // Bottom Navigation: Dots Indicator & Tombol Next / Selesai
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 12, 24, 20),
              child: Column(
                children: [
                  // Dots Indicator
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      _slides.length,
                      (idx) => AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        height: 6,
                        width: _currentPage == idx ? 28 : 8,
                        decoration: BoxDecoration(
                          color: _currentPage == idx
                              ? const Color(0xFF38BDF8)
                              : const Color(0xFF334155),
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Tombol Aksi Bawah
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _nextPage,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _currentPage == _slides.length - 1
                            ? const Color(0xFF10B981)
                            : const Color(0xFF2563EB),
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            _currentPage == _slides.length - 1
                                ? (widget.isReplay ? 'Kembali ke Menu' : 'Mulai Gunakan Aplikasi')
                                : 'Lanjutkan Slide',
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Icon(
                            _currentPage == _slides.length - 1
                                ? Icons.rocket_launch_rounded
                                : Icons.arrow_forward_rounded,
                            size: 18,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

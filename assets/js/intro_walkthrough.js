/**
 * intro_walkthrough.js - 3-Slide Onboarding & About Walkthrough
 * MaoneArt Inventory & Gudang Pabrik
 */
(function() {
  const INTRO_STORAGE_KEY = 'has_seen_inventory_intro';

  const slidesData = [
    {
      badge: 'SLIDE 1 / 3 • INVENTORY PABRIK',
      badgeColor: '#3b82f6',
      badgeBg: 'rgba(59, 130, 246, 0.15)',
      icon: 'bi-box-seam-fill',
      iconGrad: 'linear-gradient(135deg, #2563eb, #06b6d4)',
      title: 'Manajemen Stok Gudang',
      subtitle: 'Pencatatan Presisi & Terintegrasi',
      desc: 'Aplikasi cerdas untuk memantau seluruh arus keluar-masuk barang, part number mesin, dan tools kerja pabrik secara real-time dengan sinkronisasi database MariaDB.',
      highlights: [
        'Input Barang Masuk (Stock In) & Barang Keluar (Stock Out) instan',
        'Monitoring batas stok minimum untuk mencegah kehabisan part',
        'Cetak dokumen rekapitulasi mutasi format Excel & PDF resmi'
      ]
    },
    {
      badge: 'SLIDE 2 / 3 • FITUR UNGGULAN',
      badgeColor: '#10b981',
      badgeBg: 'rgba(16, 185, 129, 0.15)',
      icon: 'bi-search',
      iconGrad: 'linear-gradient(135deg, #10b981, #059669)',
      title: 'Live Search & Kontrol Stok',
      subtitle: 'Bebas Pilih & Proteksi Stok Minus',
      desc: 'Temukan barang dalam hitungan detik dengan autocomplete cerdas berdasarkan Nama, Part Number, Kode, atau Lokasi Rak simpan.',
      highlights: [
        'Filter otomatis khusus barang supplier pengirim saat barang masuk',
        'PIC bebas ambil barang dengan info stok fisik awal yang sangat jelas',
        'Proteksi ketat: cegah transaksi jika kuantitas melebihi sisa stok fisik'
      ]
    },
    {
      badge: 'SLIDE 3 / 3 • ABOUT & CREATOR',
      badgeColor: '#a855f7',
      badgeBg: 'rgba(168, 85, 247, 0.15)',
      icon: 'bi-info-circle-fill',
      iconGrad: 'linear-gradient(135deg, #8b5cf6, #ec4899)',
      title: 'Tentang Aplikasi (About)',
      subtitle: 'MaoneArt Digital Engineering',
      desc: 'Dikembangkan khusus oleh <strong>Hermawan (MaoneArt)</strong> untuk optimalisasi operasional industri pabrik. Terintegrasi penuh dengan Asisten Cerdas AI.',
      highlights: [
        'Didukung AI Assistant "Si-nya" via 9Router Gateway lokal',
        'Mode Fleksibel: Server HP Termux (127.0.0.1) & Server LAN Kantor',
        'Desain minimalis modern standar iOS Glassmorphism yang responsif'
      ]
    }
  ];

  function injectModalHtml() {
    if (document.getElementById('introWalkthroughOverlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'introWalkthroughOverlay';
    overlay.className = 'intro-overlay';
    overlay.innerHTML = `
      <div class="intro-container">
        <!-- Top Bar -->
        <div class="intro-top-bar">
          <div class="intro-brand">
            <div class="intro-brand-icon"><i class="bi bi-box-seam-fill"></i></div>
            <span>MaoneArt Inventory</span>
          </div>
          <button type="button" class="intro-skip-btn" id="btnIntroSkip">Lewati</button>
        </div>

        <!-- Slider Content -->
        <div class="intro-slide-body" id="introSlideBody">
          <!-- Dynamic Content -->
        </div>

        <!-- Bottom Controls -->
        <div class="intro-bottom-bar">
          <div class="intro-dots" id="introDots"></div>
          <button type="button" class="intro-action-btn" id="btnIntroAction">
            <span id="introActionText">Lanjutkan Slide</span>
            <i class="bi bi-arrow-right-short" id="introActionIcon" style="font-size: 1.2rem;"></i>
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
  }

  let currentSlide = 0;

  function renderSlide(idx) {
    currentSlide = idx;
    const body = document.getElementById('introSlideBody');
    const dotsContainer = document.getElementById('introDots');
    const actionText = document.getElementById('introActionText');
    const actionIcon = document.getElementById('introActionIcon');
    const actionBtn = document.getElementById('btnIntroAction');
    const skipBtn = document.getElementById('btnIntroSkip');

    if (!body) return;

    const s = slidesData[idx];

    let highlightsHtml = '';
    s.highlights.forEach(text => {
      highlightsHtml += `
        <div class="intro-highlight-item">
          <i class="bi bi-check-circle-fill" style="color: ${s.badgeColor};"></i>
          <span>${text}</span>
        </div>
      `;
    });

    body.innerHTML = `
      <div class="intro-icon-wrapper">
        <div class="intro-icon-glow" style="background: ${s.badgeBg};"></div>
        <div class="intro-icon-circle" style="background: ${s.iconGrad};">
          <i class="bi ${s.icon}"></i>
        </div>
      </div>

      <div class="intro-badge" style="color: ${s.badgeColor}; background: ${s.badgeBg}; border: 1px solid ${s.badgeColor}40;">
        ${s.badge}
      </div>

      <h2 class="intro-title">${s.title}</h2>
      <div class="intro-subtitle" style="color: ${s.badgeColor};">${s.subtitle}</div>
      <p class="intro-desc">${s.desc}</p>

      <div class="intro-highlights-box">
        ${highlightsHtml}
      </div>
    `;

    // Render Dots
    let dotsHtml = '';
    slidesData.forEach((_, i) => {
      dotsHtml += `<div class="intro-dot ${i === idx ? 'active' : ''}"></div>`;
    });
    dotsContainer.innerHTML = dotsHtml;

    // Tombol Action Text & Style
    const isLast = idx === slidesData.length - 1;
    if (isLast) {
      actionText.textContent = 'Mulai Gunakan Aplikasi';
      actionIcon.className = 'bi bi-rocket-takeoff-fill';
      actionBtn.style.background = '#10b981';
      skipBtn.style.display = 'none';
    } else {
      actionText.textContent = 'Lanjutkan Slide';
      actionIcon.className = 'bi bi-arrow-right-short';
      actionBtn.style.background = '#2563eb';
      skipBtn.style.display = 'block';
    }
  }

  function openIntro(isReplay = false) {
    injectModalHtml();
    const overlay = document.getElementById('introWalkthroughOverlay');
    renderSlide(0);

    const skipBtn = document.getElementById('btnIntroSkip');
    const actionBtn = document.getElementById('btnIntroAction');

    skipBtn.onclick = closeIntro;
    actionBtn.onclick = () => {
      if (currentSlide < slidesData.length - 1) {
        renderSlide(currentSlide + 1);
      } else {
        closeIntro();
      }
    };

    setTimeout(() => overlay.classList.add('active'), 10);
  }

  function closeIntro() {
    const overlay = document.getElementById('introWalkthroughOverlay');
    if (overlay) {
      overlay.classList.remove('active');
    }
    localStorage.setItem(INTRO_STORAGE_KEY, 'true');
  }

  // Ekspos ke global window
  window.openIntroWalkthrough = () => openIntro(true);

  // Otomatis buka pada kunjungan pertama di halaman index.php atau app.php
  document.addEventListener('DOMContentLoaded', () => {
    const hasSeen = localStorage.getItem(INTRO_STORAGE_KEY);
    const currPath = window.location.pathname.toLowerCase();
    const isMainHome = currPath.endsWith('index.php') || currPath.endsWith('app.php') || currPath.endsWith('/inventory/') || currPath.endsWith('/inventory');

    if (!hasSeen && isMainHome) {
      setTimeout(() => {
        openIntro(false);
      }, 300);
    }
  });
})();

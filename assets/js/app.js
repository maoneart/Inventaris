/* ==========================================================================
   MaoneArt Application Logic & Glassmorphism Modal Standards
   ========================================================================== */

/**
 * Pastikan Container Modal Tersedia di DOM
 */
function ensureModalContainer() {
  let container = document.getElementById('maoneartModalOverlay');
  if (!container) {
    container = document.createElement('div');
    container.id = 'maoneartModalOverlay';
    container.className = 'maoneart-modal-overlay';
    document.body.appendChild(container);
  }
  return container;
}

/**
 * MaoneArt Glassmorphism Confirmation Modal (Wajib Symmetrical 2-Kolom Grid)
 */
function showConfirmModal(options = {}) {
  const {
    title = 'Konfirmasi Tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    confirmText = 'Ya, Lanjutkan',
    cancelText = 'Batal',
    isDanger = true,
    icon = 'bi-exclamation-triangle-fill',
    onConfirm = null
  } = options;

  const container = ensureModalContainer();
  const iconClass = isDanger ? 'danger' : 'info';
  const confirmBtnClass = isDanger ? 'danger' : 'primary';

  container.innerHTML = `
    <div class="maoneart-modal-card">
      <div class="maoneart-modal-icon-box ${iconClass}">
        <i class="bi ${icon}"></i>
      </div>
      <h3 class="maoneart-modal-title">${title}</h3>
      <p class="maoneart-modal-message">${message}</p>
      <div class="maoneart-modal-actions" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; width: 100%;">
        <button type="button" class="maoneart-modal-btn cancel" id="maoneartModalCancel" style="width: 100%; height: 44px;">
          ${cancelText}
        </button>
        <button type="button" class="maoneart-modal-btn ${confirmBtnClass}" id="maoneartModalConfirm" style="width: 100%; height: 44px;">
          ${confirmText}
        </button>
      </div>
    </div>
  `;

  setTimeout(() => container.classList.add('active'), 10);

  const close = () => {
    container.classList.remove('active');
  };

  const cancelBtn = container.querySelector('#maoneartModalCancel');
  const confirmBtn = container.querySelector('#maoneartModalConfirm');

  cancelBtn.onclick = close;
  confirmBtn.onclick = () => {
    close();
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
  };

  container.onclick = (e) => {
    if (e.target === container) close();
  };
}

/**
 * MaoneArt Glassmorphism Alert Modal (Pengganti alert() native)
 */
function showAlertModal(options = {}) {
  const {
    title = 'Informasi',
    message = '',
    buttonText = 'Mengerti',
    icon = 'bi-info-circle-fill',
    type = 'info' // 'info', 'success', 'danger'
  } = options;

  const container = ensureModalContainer();
  const btnClass = type === 'danger' ? 'danger' : (type === 'success' ? 'success' : 'primary');

  container.innerHTML = `
    <div class="maoneart-modal-card">
      <div class="maoneart-modal-icon-box ${type}">
        <i class="bi ${icon}"></i>
      </div>
      <h3 class="maoneart-modal-title">${title}</h3>
      <p class="maoneart-modal-message">${message}</p>
      <div style="width: 100%;">
        <button type="button" class="maoneart-modal-btn ${btnClass}" id="maoneartAlertOk" style="width: 100%; height: 44px;">
          ${buttonText}
        </button>
      </div>
    </div>
  `;

  setTimeout(() => container.classList.add('active'), 10);

  const close = () => {
    container.classList.remove('active');
  };

  const okBtn = container.querySelector('#maoneartAlertOk');
  okBtn.onclick = close;

  container.onclick = (e) => {
    if (e.target === container) close();
  };
}

/**
 * Shortcut Konfirmasi Hapus Data
 */
function confirmDelete(url, itemName) {
  showConfirmModal({
    title: 'Hapus Data?',
    message: `Apakah Anda yakin ingin menghapus data <strong>"${itemName}"</strong>? Data transaksi terkait mungkin terpengaruh.`,
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    isDanger: true,
    icon: 'bi-trash-fill',
    onConfirm: () => {
      window.location.href = url;
    }
  });
}

// Inisialisasi Event Listener Umum
document.addEventListener('DOMContentLoaded', () => {
  // Cek Flash Message dari Server
  const flashData = window._FLASH_MESSAGE_;
  if (flashData && flashData.message) {
    let icon = 'bi-info-circle-fill';
    if (flashData.type === 'success') icon = 'bi-check-circle-fill';
    if (flashData.type === 'danger') icon = 'bi-exclamation-octagon-fill';

    showAlertModal({
      title: flashData.title || 'Notifikasi',
      message: flashData.message,
      type: flashData.type || 'info',
      icon: icon
    });
  }

  // Filter Tabel Pencarian Cepat
  const searchInput = document.getElementById('tableSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.toLowerCase();
      const rows = document.querySelectorAll('.modern-table tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }
});

/* ==========================================================================
   SLIDE-LEFT SIDEBAR DRAWER CONTROLLER
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
  const drawer = document.getElementById('sidebarDrawer');
  const backdrop = document.getElementById('sidebarBackdrop');
  const btnOpen = document.getElementById('btnOpenSidebar');
  const btnClose = document.getElementById('btnCloseSidebar');

  function openSidebar() {
    if (drawer) drawer.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (drawer) drawer.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (btnOpen) btnOpen.addEventListener('click', openSidebar);
  if (btnClose) btnClose.addEventListener('click', closeSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  // Close on ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('active')) {
      closeSidebar();
    }
  });

  // Simple swipe left gesture on mobile
  let touchStartX = 0;
  if (drawer) {
    drawer.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    drawer.addEventListener('touchend', (e) => {
      const touchEndX = e.changedTouches[0].screenX;
      if (touchStartX - touchEndX > 50) {
        closeSidebar();
      }
    }, { passive: true });
  }
});

/* ==========================================================================
   THEME CONTROLLER (LIGHT & DARK MODE TOGGLE)
   ========================================================================== */
function setAppTheme(theme) {
  const selectedTheme = (theme === 'light') ? 'light' : 'dark';
  localStorage.setItem('maoneart_theme', selectedTheme);
  document.documentElement.setAttribute('data-theme', selectedTheme);
  if (selectedTheme === 'light') {
    document.documentElement.classList.add('theme-light');
    if (document.body) {
      document.body.classList.add('theme-light');
      document.body.classList.remove('theme-dark');
    }
  } else {
    document.documentElement.classList.remove('theme-light');
    if (document.body) {
      document.body.classList.remove('theme-light');
      document.body.classList.add('theme-dark');
    }
  }

  // Update Visual State in Settings if present
  const darkCard = document.getElementById('themeCardDark');
  const lightCard = document.getElementById('themeCardLight');
  if (darkCard && lightCard) {
    darkCard.classList.toggle('active', selectedTheme === 'dark');
    lightCard.classList.toggle('active', selectedTheme === 'light');
  }
}
window.setAppTheme = setAppTheme;

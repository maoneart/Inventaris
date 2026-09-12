<?php
// includes/footer.php
$flash = getFlash();
$currPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- iOS Minimalist Copyright Footer -->
<footer class="ios-footer-copyright">
  <p>© <?= date('Y') ?> <strong>MaoneArt</strong> · <a href="https://maoneart.my.id" target="_blank" rel="noopener">Maoneart.my.id</a></p>
  <p class="ios-footer-tagline">Sistem Manajemen Stok & Pergudangan Modern</p>
</footer>

</main> <!-- End .main-wrapper -->

<!-- Mobile Bottom Dock (Khusus Layar HP: Home, Masuk, Tanya AI, Keluar, Pengaturan) -->
<nav class="mobile-dock">
  <a href="index.php" class="dock-item <?= in_array($currPage, ['index', 'app', '']) ? 'active' : '' ?>">
    <i class="bi bi-house-door-fill"></i>
    <span>Home</span>
  </a>
  <a href="masuk.php" class="dock-item <?= $currPage === 'masuk' ? 'active' : '' ?>">
    <i class="bi bi-box-arrow-in-down"></i>
    <span>Masuk</span>
  </a>
  <a href="tanya_ai.php" class="dock-item <?= $currPage === 'tanya_ai' ? 'active' : '' ?> dock-highlight" title="Tanya Si-nya">
    <i class="bi bi-robot"></i>
    <span>Tanya AI</span>
  </a>
  <a href="keluar.php" class="dock-item <?= $currPage === 'keluar' ? 'active' : '' ?>">
    <i class="bi bi-box-arrow-up-right"></i>
    <span>Keluar</span>
  </a>
  <a href="pengaturan.php" class="dock-item <?= $currPage === 'pengaturan' ? 'active' : '' ?>">
    <i class="bi bi-gear-fill"></i>
    <span>Pengaturan</span>
  </a>
</nav>

<script>
  window._FLASH_MESSAGE_ = <?= json_encode($flash) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>

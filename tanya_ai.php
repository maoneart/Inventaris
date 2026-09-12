<?php
// tanya_ai.php - Antarmuka Chat "Tanya Si-nya" (AI Assistant Gudang)
$pageTitle = "Tanya Si-nya (AI Gudang)";
require_once __DIR__ . '/includes/header.php';
?>

<!-- iOS Minimalist Header Ala iPhone (Exact Screenshot) -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Tanya Si-nya</h1>
  <div class="ios-bar-action">
    <button type="button" id="btnOpenTokenModal" class="btn-pill-action btn-pill-purple">
      <i class="bi bi-key-fill text-warning"></i> <span id="tokenStatusText">Atur Token</span>
    </button>
  </div>
</div>

<!-- Modal Atur Token Gemini Manual (Aman, Tidak Masuk Kodingan / GitHub) -->
<div id="modalGeminiToken" class="maoneart-modal-overlay" style="display: none;">
  <div class="maoneart-modal-card" style="max-width: 420px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-shield-lock-fill text-primary"></i> Token Gemini AI
      </h3>
      <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modalGeminiToken').style.display='none'">✕</button>
    </div>

    <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
      🔒 <strong>Keamanan Terjamin:</strong> Token hanya disimpan di penyimpanan lokal browser/HP Anda (LocalStorage). Tidak disimpan di file kodingan sehingga <strong>aman dari risiko terambil orang di GitHub</strong>.
    </p>

    <div class="form-group">
      <label class="ios-label">Masukkan API Key Google Gemini</label>
      <input type="password" id="inputGeminiKey" class="ios-input" placeholder="AIzaSy... atau token Gemini Anda" autocomplete="off">
    </div>

    <div class="maoneart-modal-actions" style="margin-top: 20px;">
      <button type="button" class="maoneart-modal-btn cancel" onclick="document.getElementById('modalGeminiToken').style.display='none'">Batal</button>
      <button type="button" id="btnSaveToken" class="maoneart-modal-btn primary">Simpan Token</button>
    </div>
  </div>
</div>

<!-- Chat Shell Container iOS Style -->
<div class="ios-form-card" style="display: flex; flex-direction: column; height: 72vh; max-height: 750px; padding: 16px; position: relative;">
  
  <!-- Chat Messages Stream -->
  <div id="aiChatBox" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; padding-right: 6px;">
    
    <!-- Welcome Bubble -->
    <div style="display: flex; gap: 12px; align-items: flex-start;">
      <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; flex-shrink: 0; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);">
        🤖
      </div>
      <div class="ai-bubble" style="border: 1px solid var(--card-border); border-radius: 4px 18px 18px 18px; padding: 14px 18px; font-size: 0.88rem; line-height: 1.6; max-width: 85%; color: var(--text-main); background: var(--input-bg);">
        <strong>Halo! Saya Si-nya, Asisten AI Gudang MaoneArt 🤖✨</strong><br><br>
        Saya terhubung langsung ke database stok gudang kantor. Anda bisa tanya atau instruksikan apa saja:<br>
        • 📦 <em>"Berapa stok Kunci Pas dan Kawat Las saat ini?"</em><br>
        • ⚠️ <em>"Barang apa saja yang stoknya sudah kritis dan harus dipesan ke supplier?"</em><br>
        • 👷 <em>"Siapa saja PIC yang baru-baru ini mengambil barang?"</em><br>
        • 📄 <em>"Tolong buatkan draf ringkasan laporan dan siapkan link download PDF & Excel!"</em>
      </div>
    </div>

  </div>

  <!-- Quick Suggestion Chips -->
  <div style="display: flex; gap: 8px; overflow-x: auto; padding: 10px 0 6px; scrollbar-width: none; flex-shrink: 0;">
    <button type="button" class="btn btn-secondary btn-sm js-quick-chip" data-msg="Barang apa saja yang stoknya sudah menipis atau kritis?">
      ⚠️ Stok Kritis
    </button>
    <button type="button" class="btn btn-secondary btn-sm js-quick-chip" data-msg="Tolong buatkan rekap mutasi barang dan berikan link download PDF & Excel">
      📊 Minta PDF & Excel
    </button>
    <button type="button" class="btn btn-secondary btn-sm js-quick-chip" data-msg="Berapa sisa baut, oli hidrolik, dan kawat las di rak sekarang?">
      🔍 Cek Sisa Stok
    </button>
    <button type="button" class="btn btn-secondary btn-sm js-quick-chip" data-msg="Siapa saja PIC yang mengambil tools minggu ini dan untuk keperluan apa?">
      👷 Aktivitas PIC
    </button>
  </div>

  <!-- Input Bar -->
  <div style="display: flex; gap: 10px; align-items: center; padding-top: 10px; border-top: 1px solid var(--card-border);">
    <input type="text" id="aiInput" class="ios-input" placeholder="Ketik pertanyaan atau minta buatkan laporan gudang..." autocomplete="off" style="padding: 12px 16px; font-size: 0.9rem;">
    <button type="button" id="btnSendAi" class="ios-btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); height: 46px; width: auto; padding: 0 20px; flex-shrink: 0; box-shadow: 0 4px 14px rgba(139, 92, 246, 0.4);">
      <i class="bi bi-send-fill"></i> Kirim
    </button>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const chatBox = document.getElementById('aiChatBox');
  const aiInput = document.getElementById('aiInput');
  const btnSend = document.getElementById('btnSendAi');
  const tokenModal = document.getElementById('modalGeminiToken');
  const btnOpenTokenModal = document.getElementById('btnOpenTokenModal');
  const inputGeminiKey = document.getElementById('inputGeminiKey');
  const btnSaveToken = document.getElementById('btnSaveToken');
  const tokenStatusText = document.getElementById('tokenStatusText');

  // Cek Token yang sudah tersimpan di browser
  function updateTokenStatus() {
    const savedKey = localStorage.getItem('maoneart_gemini_token') || '';
    if (savedKey) {
      tokenStatusText.innerText = 'Token Tersimpan ✓';
      inputGeminiKey.value = savedKey;
    } else {
      tokenStatusText.innerText = 'Atur Token Gemini';
    }
  }
  updateTokenStatus();

  btnOpenTokenModal.addEventListener('click', () => {
    tokenModal.style.display = 'flex';
  });

  btnSaveToken.addEventListener('click', () => {
    const keyVal = inputGeminiKey.value.trim();
    if (keyVal) {
      localStorage.setItem('maoneart_gemini_token', keyVal);
      showAlertModal({
        title: 'Token Tersimpan',
        message: 'Token Gemini berhasil disimpan aman di browser Anda. Kodingan di server tetap bersih & aman.',
        type: 'success'
      });
    } else {
      localStorage.removeItem('maoneart_gemini_token');
    }
    updateTokenStatus();
    tokenModal.style.display = 'none';
  });

  function appendMessage(sender, htmlContent) {
    const isUser = sender === 'user';
    const row = document.createElement('div');
    row.style.cssText = `display: flex; gap: 12px; align-items: flex-start; justify-content: ${isUser ? 'flex-end' : 'flex-start'};`;

    if (isUser) {
      row.innerHTML = `
        <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; border-radius: 18px 4px 18px 18px; padding: 12px 16px; font-size: 0.88rem; max-width: 80%; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
          ${htmlContent}
        </div>
      `;
    } else {
      row.innerHTML = `
        <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; flex-shrink: 0;">
          🤖
        </div>
        <div class="ai-bubble" style="border: 1px solid var(--card-border); border-radius: 4px 18px 18px 18px; padding: 14px 18px; font-size: 0.88rem; line-height: 1.6; max-width: 85%; color: var(--text-main); background: var(--input-bg);">
          ${htmlContent}
        </div>
      `;
    }

    chatBox.appendChild(row);
    chatBox.scrollTop = chatBox.scrollHeight;
    return row;
  }

  function formatMarkdown(md) {
    if (!md) return '';
    let html = md;
    
    html = html.replace(/^### (.*$)/gim, '<h4 style="font-size: 0.95rem; font-weight: 800; color: #2563eb; margin: 10px 0 6px 0;">$1</h4>');
    html = html.replace(/^## (.*$)/gim, '<h3 style="font-size: 1.05rem; font-weight: 800; color: #7c3aed; margin: 12px 0 8px 0;">$1</h3>');
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong style="color: var(--text-main); font-weight: 800;">$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; margin: 3px 2px; padding: 4px 10px; text-decoration: none;">$1</a>');
    html = html.replace(/^\s*[\*\-]\s+(.*$)/gim, '<div style="display: flex; gap: 6px; margin: 4px 0;"><span style="color: #2563eb;">•</span><div>$1</div></div>');
    html = html.replace(/^\s*(\d+)\.\s+(.*$)/gim, '<div style="display: flex; gap: 6px; margin: 4px 0;"><strong style="color: #7c3aed;">$1.</strong><div>$2</div></div>');
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  async function handleSend() {
    const text = aiInput.value.trim();
    if (!text) return;

    appendMessage('user', text);
    aiInput.value = '';

    const typingIndicator = appendMessage('ai', '<i class="bi bi-three-dots"></i> <em>Si-nya sedang memeriksa inventory gudang...</em>');
    const token = localStorage.getItem('maoneart_gemini_token') || '';

    try {
      const response = await fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, api_key: token })
      });
      const data = await response.json();
      
      typingIndicator.remove();
      if (data.success && data.reply) {
        appendMessage('ai', formatMarkdown(data.reply));
      } else {
        appendMessage('ai', 'Maaf, terjadi kendala saat memproses: ' + (data.reply || 'Respon kosong'));
      }
    } catch (err) {
      typingIndicator.remove();
      appendMessage('ai', 'Gagal menghubungi server AI: ' + err.message);
    }
  }

  btnSend.addEventListener('click', handleSend);
  aiInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') handleSend();
  });

  document.querySelectorAll('.js-quick-chip').forEach(btn => {
    btn.addEventListener('click', function() {
      aiInput.value = this.getAttribute('data-msg');
      handleSend();
    });
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

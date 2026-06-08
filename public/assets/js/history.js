// public/assets/js/history.js
// Search, filter, and modal for Riwayat Pengajuan page

(function () {
  'use strict';

  const historyData  = window.EMS_HISTORY || [];

  const searchInput  = document.getElementById('history-search');
  const typeFilter   = document.getElementById('history-type-filter');
  const statusFilter = document.getElementById('history-status-filter');
  const tableBody    = document.getElementById('history-tbody');
  const resultCount  = document.getElementById('history-result-count');

  const modal        = document.getElementById('history-modal');
  const modalClose   = document.getElementById('history-modal-close');

  // ---- Helpers ----
  function formatDateShort(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  }

  function formatDateYear(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { year: 'numeric' });
  }

  function formatDateLong(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
  }

  function typeBadgeHtml(type) {
    if (type === 'cuti') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #bfdbfe;color:#2563eb;background:#eff6ff;">Cuti</span>`;
    }
    return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
              font-size:0.75rem;font-weight:500;
              border:1.5px solid #e9d5ff;color:#9333ea;background:#faf5ff;">Lembur</span>`;
  }

  function statusBadgeHtml(status) {
    if (status === 'approved') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #bbf7d0;color:#16a34a;background:#f0fdf4;">Disetujui</span>`;
    }
    if (status === 'rejected') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #fecaca;color:#dc2626;background:#fef2f2;">Ditolak</span>`;
    }
    return '';
  }

  // ---- Render Table ----
  function renderTable(data) {
    if (data.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <i data-lucide="inbox" style="display:block;margin:0 auto 0.75rem;"></i>
              <p>Tidak ada riwayat yang ditemukan</p>
            </div>
          </td>
        </tr>`;
      if (typeof lucide !== 'undefined') lucide.createIcons();
      resultCount.textContent = '0 pengajuan ditemukan';
      return;
    }

    resultCount.textContent = `${data.length} pengajuan ditemukan`;

    tableBody.innerHTML = data.map(item => {
      // Detail cell content
      let detailHtml = '';
      if (item.type === 'cuti') {
        detailHtml = `
          <div class="emp-name">${item.leaveType}</div>
          <div class="emp-email">${item.duration} hari</div>`;
      } else {
        detailHtml = `
          <div class="emp-name">${formatDateShort(item.overtimeDate)}</div>
          <div class="emp-email">${item.startTime}–${item.endTime} (${item.hours} jam)</div>`;
      }

      return `
        <tr data-id="${item.id}">
          <td>
            <div class="emp-cell">
              <div class="avatar">
                <img src="${item.avatar}" alt="Avatar ${item.name}"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
                <span class="avatar-fallback" style="display:none;">${item.name.charAt(0)}</span>
              </div>
              <div>
                <div class="emp-name">${item.name}</div>
                <div class="emp-email">${item.employeeId}</div>
              </div>
            </div>
          </td>
          <td>${typeBadgeHtml(item.type)}</td>
          <td>${detailHtml}</td>
          <td>
            <div class="emp-name">${formatDateShort(item.submittedDate)}</div>
            <div class="emp-email">${formatDateYear(item.submittedDate)}</div>
          </td>
          <td>${statusBadgeHtml(item.status)}</td>
          <td>
            <div class="emp-name">Admin</div>
            <div class="emp-email">${formatDateShort(item.processedDate)}</div>
          </td>
          <td style="text-align:center;">
            <button class="btn-icon btn-view-history" data-id="${item.id}" title="Lihat Detail">
              <i data-lucide="eye"></i>
            </button>
          </td>
        </tr>`;
    }).join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Bind view buttons
    document.querySelectorAll('.btn-view-history').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const item = historyData.find(h => h.id === id);
        if (item) openModal(item);
      });
    });
  }

  // ---- Filter ----
  function applyFilters() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const t = typeFilter?.value   || 'all';
    const s = statusFilter?.value || 'all';

    const filtered = historyData.filter(item => {
      const matchSearch = !q ||
        item.name.toLowerCase().includes(q) ||
        item.employeeId.toLowerCase().includes(q) ||
        (item.leaveType && item.leaveType.toLowerCase().includes(q));
      const matchType   = t === 'all' || item.type   === t;
      const matchStatus = s === 'all' || item.status === s;
      return matchSearch && matchType && matchStatus;
    });

    renderTable(filtered);
  }

  // ---- Modal ----
  function openModal(item) {
    // Reset avatar state
    const hmAvatar   = document.getElementById('hm-avatar');
    const hmFallback = document.getElementById('hm-avatar-fallback');
    hmAvatar.style.display = 'block';
    if (hmFallback) hmFallback.style.display = 'none';
    hmAvatar.src = item.avatar;
    hmAvatar.alt = 'Avatar ' + item.name;
    if (hmFallback) hmFallback.textContent = item.name.charAt(0);

    document.getElementById('hm-name').textContent     = item.name;
    document.getElementById('hm-emp-code').textContent = item.employeeId;
    document.getElementById('hm-type-badge').innerHTML = typeBadgeHtml(item.type);
    document.getElementById('h-modal-desc').textContent =
      'Informasi lengkap pengajuan ' + (item.type === 'cuti' ? 'cuti' : 'lembur');

    // Dynamic fields per type
    let dynamicHtml = '';
    if (item.type === 'cuti') {
      dynamicHtml = `
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Jenis Cuti</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${item.leaveType}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Tanggal Mulai</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${formatDateLong(item.startDate)}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Tanggal Selesai</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${formatDateLong(item.endDate)}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Durasi</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${item.duration} hari kerja</p>
        </div>`;
    } else {
      dynamicHtml = `
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Tanggal Lembur</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${formatDateLong(item.overtimeDate)}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Jam Mulai</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${item.startTime}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Jam Selesai</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${item.endTime}</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Durasi</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">${item.hours} jam</p>
        </div>`;
    }
    document.getElementById('hm-dynamic-details').innerHTML = dynamicHtml;

    document.getElementById('hm-reason').textContent         = item.reason || '—';
    document.getElementById('hm-submitted-date').textContent = formatDateLong(item.submittedDate);
    document.getElementById('hm-processed-date').textContent = formatDateLong(item.processedDate);
    document.getElementById('hm-status-badge').innerHTML     = statusBadgeHtml(item.status);

    const rejBox = document.getElementById('hm-rejection-box');
    if (item.status === 'rejected' && item.rejectionReason) {
      rejBox.style.display = 'block';
      document.getElementById('hm-rejection-reason').textContent = item.rejectionReason;
    } else {
      rejBox.style.display = 'none';
    }

    modal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.remove('modal-open');
    document.body.style.overflow = '';
  }

  // ---- Event Listeners ----
  document.addEventListener('DOMContentLoaded', function () {
    renderTable(historyData);

    searchInput?.addEventListener('input',  applyFilters);
    typeFilter?.addEventListener('change',  applyFilters);
    statusFilter?.addEventListener('change', applyFilters);

    modalClose?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  });

})();

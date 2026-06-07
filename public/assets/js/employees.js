// public/assets/js/employees.js
// Search, filter, and modal functionality for Data Pegawai page

(function () {
  'use strict';

  // ---- Employee Data (embedded from PHP via JSON) ----
  // Populated by inline <script> in the view
  const employees = window.EMS_EMPLOYEES || [];

  // ---- DOM References ----
  const searchInput   = document.getElementById('emp-search');
  const deptFilter    = document.getElementById('emp-dept-filter');
  const statusFilter  = document.getElementById('emp-status-filter');
  const tableBody     = document.getElementById('emp-tbody');
  const resultCount   = document.getElementById('emp-result-count');

  // Modal
  const modal         = document.getElementById('emp-modal');
  const modalClose    = document.getElementById('emp-modal-close');

  // Modal fields
  const mAvatar     = document.getElementById('m-avatar');
  const mName       = document.getElementById('m-name');
  const mPosition   = document.getElementById('m-position');
  const mStatusBadge= document.getElementById('m-status-badge');
  const mDeptBadge  = document.getElementById('m-dept-badge');
  const mId         = document.getElementById('m-id');
  const mEmail      = document.getElementById('m-email');
  const mDept       = document.getElementById('m-dept');
  const mJoinDate   = document.getElementById('m-join-date');
  const mTotal      = document.getElementById('m-total');
  const mUsed       = document.getElementById('m-used');
  const mRemaining  = document.getElementById('m-remaining');
  const mProgressFill = document.getElementById('m-progress-fill');
  const mProgressPct  = document.getElementById('m-progress-pct');

  // ---- Helpers ----
  function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function formatDateLong(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function statusBadgeHtml(status, activity) {
    // Jika ada aktivitas hari ini → tampilkan badge aktivitas (menggantikan Aktif)
    if (activity === 'cuti') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #f59e0b;color:#d97706;background:transparent;">
                Sedang Cuti</span>`;
    }
    if (activity === 'lembur') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #8b5cf6;color:#7c3aed;background:transparent;">
                Sedang Lembur</span>`;
    }
    // Status normal
    if (status === 'active') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #22c55e;color:#16a34a;background:transparent;">
                Aktif</span>`;
    }
    if (status === 'inactive') {
      return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
                font-size:0.75rem;font-weight:500;
                border:1.5px solid #94a3b8;color:#64748b;background:transparent;">
                Tidak Aktif</span>`;
    }
    return `<span style="display:inline-block;padding:3px 10px;border-radius:20px;
              font-size:0.75rem;font-weight:500;
              border:1.5px solid #94a3b8;color:#64748b;background:transparent;">
              ${status}</span>`;
  }

  // ---- Render Table ---- 
  function renderTable(data) {
    if (data.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <i data-lucide="search-x" style="display:block;margin:0 auto 0.75rem;"></i>
              <p>Tidak ada pegawai yang ditemukan</p>
            </div>
          </td>
        </tr>`;
      if (typeof lucide !== 'undefined') lucide.createIcons();
      resultCount.textContent = '0 pegawai ditemukan';
      return;
    }

    resultCount.textContent = `${data.length} pegawai ditemukan`;

    tableBody.innerHTML = data.map(emp => {
      const pct = Math.round((emp.usedLeave / emp.totalLeave) * 100);
      return `
      <tr data-id="${emp.id}">
        <td>
          <div class="emp-cell">
            <div class="avatar">
              <img src="${emp.avatar}" alt="Avatar ${emp.name}"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
              <span class="avatar-fallback" style="display:none">${emp.name.charAt(0)}</span>
            </div>
            <div>
              <div class="emp-name">${emp.name}</div>
              <div class="emp-email">${emp.email}</div>
            </div>
          </div>
        </td>
        <td>${emp.department}</td>
        <td>${emp.position}</td>
        <td>${formatDate(emp.joinDate)}</td>
        <td class="leave-quota-cell">
          <div class="leave-quota-label">Sisa: ${emp.remainingLeave}/${emp.totalLeave}</div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:${pct}%"></div>
          </div>
        </td>
        <td>${statusBadgeHtml(emp.status, emp.currentActivity)}</td>
        <td>
          <button class="btn-icon btn-view-emp" data-id="${emp.id}" title="Lihat Detail">
            <i data-lucide="eye"></i>
          </button>
        </td>
      </tr>`;
    }).join('');

    // Re-init lucide icons for newly rendered rows
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Bind view buttons
    document.querySelectorAll('.btn-view-emp').forEach(btn => {
      btn.addEventListener('click', function () {
        const id  = this.getAttribute('data-id');
        const emp = employees.find(e => e.id === id);
        if (emp) openModal(emp);
      });
    });
  }

  // ---- Filter ---- 
  function applyFilters() {
    const q    = (searchInput?.value || '').toLowerCase().trim();
    const dept = deptFilter?.value   || 'all';
    const st   = statusFilter?.value || 'all';

    const filtered = employees.filter(emp => {
      const matchSearch = !q ||
        emp.name.toLowerCase().includes(q) ||
        emp.id.toLowerCase().includes(q)   ||
        emp.email.toLowerCase().includes(q);
      const matchDept   = dept === 'all' || emp.department === dept;
      const matchStatus = st   === 'all' || emp.status    === st;
      return matchSearch && matchDept && matchStatus;
    });

    renderTable(filtered);
  }

  // ---- Modal ---- 
  function openModal(emp) {
    const pct = Math.round((emp.usedLeave / emp.totalLeave) * 100);

    mAvatar.src = emp.avatar;
    mAvatar.alt = 'Avatar ' + emp.name;
    mName.textContent     = emp.name;
    mPosition.textContent = emp.position;

    const statusMap = {
      'active':   { cls: 'badge-approved', label: 'Aktif' },
      'on-leave': { cls: 'badge-pending',  label: 'Sedang Cuti' },
      'inactive': { cls: 'badge-rejected', label: 'Tidak Aktif' },
    };
    const s = statusMap[emp.status] || { cls: 'badge-pending', label: emp.status };
    mStatusBadge.className   = `badge ${s.cls}`;
    mStatusBadge.textContent = s.label;

    // Activity badge di modal
    const mActivityBadge = document.getElementById('m-activity-badge');
    if (mActivityBadge) {
      if (emp.currentActivity === 'cuti') {
        mActivityBadge.style.display = '';
        mActivityBadge.textContent   = '🏖 Sedang Cuti';
        mActivityBadge.style.cssText += 'background:#fef3c7;color:#92400e;border:1px solid #fde68a;';
      } else if (emp.currentActivity === 'lembur') {
        mActivityBadge.style.display = '';
        mActivityBadge.textContent   = '⏰ Sedang Lembur';
        mActivityBadge.style.cssText += 'background:#f3e8ff;color:#6b21a8;border:1px solid #e9d5ff;';
      } else {
        mActivityBadge.style.display = 'none';
      }
    }

    mDeptBadge.textContent  = emp.department;
    mId.textContent         = emp.id;
    mEmail.textContent      = emp.email;
    mDept.textContent       = emp.department;
    mJoinDate.textContent   = formatDateLong(emp.joinDate);
    mTotal.textContent      = emp.totalLeave + ' hari';
    mUsed.textContent       = emp.usedLeave  + ' hari';
    mRemaining.textContent  = emp.remainingLeave + ' hari';

    mProgressFill.style.width = '0%';
    mProgressPct.textContent  = pct + '% terpakai';

    modal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';

    // Animate progress bar after open
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        mProgressFill.style.transition = 'width 600ms cubic-bezier(0.4,0,0.2,1)';
        mProgressFill.style.width = pct + '%';
      });
    });
  }

  function closeModal() {
    modal.classList.remove('modal-open');
    document.body.style.overflow = '';
  }

  // ---- Event Listeners ---- 
  document.addEventListener('DOMContentLoaded', function () {
    // Initial render
    renderTable(employees);

    // Search & filters
    searchInput?.addEventListener('input',  applyFilters);
    deptFilter?.addEventListener('change',  applyFilters);
    statusFilter?.addEventListener('change', applyFilters);

    // Modal close
    modalClose?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  });

})();

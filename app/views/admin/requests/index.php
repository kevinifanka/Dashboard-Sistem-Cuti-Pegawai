<?php
// app/views/admin/requests/index.php — Permohonan Cuti

// $requests & $counts come from AdminDashboardController::requests()
// Normalize DB row keys → view keys used in HTML below
$requests = array_map(function(array $r): array {
  return [
    'numId'      => (int)$r['id'],          // numeric DB id for API
    'id'         => $r['request_code'],      // display code (LR001)
    'employeeId' => $r['emp_code'],
    'name'       => $r['employee_name'],
    'avatar'     => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($r['avatar_seed'] ?? 'User'),
    'dept'       => $r['department_name'],
    'type'       => $r['leave_type_name'],
    'start'      => $r['start_date'],
    'end'        => $r['end_date'],
    'days'       => (int)$r['duration_days'],
    'reason'     => $r['reason'] ?? '',
    'status'     => $r['status'],
    'submitted'  => substr($r['submitted_at'], 0, 10),
  ];
}, $requests ?? []);

$pending  = array_filter($requests, fn($r) => $r['status'] === 'pending');
$approved = array_filter($requests, fn($r) => $r['status'] === 'approved');
$rejected = array_filter($requests, fn($r) => $r['status'] === 'rejected');

// Use counts from model if available, else compute from array
$pendingCount  = $counts['pending']  ?? count($pending);
$approvedCount = $counts['approved'] ?? count($approved);
$rejectedCount = $counts['rejected'] ?? count($rejected);

// Badge helper
function statusBadge(string $status): string {
  return match($status) {
    'pending'  => '<span class="badge badge-pending">Pending</span>',
    'approved' => '<span class="badge badge-approved">Disetujui</span>',
    'rejected' => '<span class="badge badge-rejected">Ditolak</span>',
    default    => '',
  };
}

// Render table rows
function renderRows(array $rows): void {
  if (empty($rows)):
    echo '<tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--color-gray-400);">Tidak ada permohonan</td></tr>';
    return;
  endif;
  foreach ($rows as $r):
    // JSON encode for JS detail modal
    $json = htmlspecialchars(json_encode($r), ENT_QUOTES);
?>
    <tr class="req-row"
        data-status="<?= htmlspecialchars($r['status']) ?>"
        data-dept="<?= htmlspecialchars($r['dept']) ?>"
        data-type="<?= htmlspecialchars($r['type']) ?>"
        data-name="<?= htmlspecialchars(strtolower($r['name'])) ?>"
        data-json="<?= $json ?>">

      <!-- Pegawai -->
      <td>
        <div class="user-cell">
          <div class="avatar avatar-sm">
            <img src="<?= htmlspecialchars($r['avatar']) ?>" alt=""
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
            <span class="avatar-fallback" style="display:none"><?= htmlspecialchars(mb_substr($r['name'], 0, 1)) ?></span>
          </div>
          <div class="user-cell-info">
            <div class="user-cell-name"><?= htmlspecialchars($r['name']) ?></div>
            <div class="user-cell-sub"><?= htmlspecialchars($r['employeeId']) ?></div>
          </div>
        </div>
      </td>

      <!-- Departemen -->
      <td><?= htmlspecialchars($r['dept']) ?></td>

      <!-- Jenis Cuti -->
      <td><?= htmlspecialchars($r['type']) ?></td>

      <!-- Tanggal -->
      <td>
        <div style="font-size:var(--font-size-sm);">
          <div class="user-cell-name"><?= date('d M', strtotime($r['start'])) ?></div>
          <div class="user-cell-sub">s/d <?= date('d M Y', strtotime($r['end'])) ?></div>
        </div>
      </td>

      <!-- Durasi -->
      <td><?= (int)$r['days'] ?> hari</td>

      <!-- Status -->
      <td><?= statusBadge($r['status']) ?></td>

      <!-- Aksi -->
      <td>
        <div class="action-group">
          <!-- Detail button -->
          <button class="btn-icon" title="Lihat Detail"
                  onclick="openDetail(this.closest('tr').dataset.json)">
            <i data-lucide="eye"></i>
          </button>

          <?php if ($r['status'] === 'pending'): ?>
          <!-- Approve -->
          <button class="btn-icon btn-ghost-green" title="Setujui"
                  onclick="openApprove('<?= htmlspecialchars($r['id']) ?>', '<?= htmlspecialchars($r['name']) ?>', this)">
            <i data-lucide="check-circle"></i>
          </button>
          <!-- Reject -->
          <button class="btn-icon btn-ghost-red" title="Tolak"
                  onclick="openReject('<?= htmlspecialchars($r['id']) ?>', '<?= htmlspecialchars($r['name']) ?>', this)">
            <i data-lucide="x-circle"></i>
          </button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
<?php
  endforeach;
}
?>

<!-- ===================== PAGE ===================== -->
<div class="page-header">
  <h2>Manajemen Permohonan Cuti</h2>
  <p>Kelola dan tinjau semua permohonan cuti pegawai</p>
</div>

<div class="card">
  <!-- Card Header: Title + Filters — same layout as Data Pegawai -->
  <div class="card-header">
    <div class="card-toolbar">
      <div>
        <h3 class="card-title">Daftar Permohonan</h3>
        <p class="card-description">Total <?= count($requests) ?> permohonan</p>
      </div>

      <div class="card-toolbar-actions">
        <!-- Search -->
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input id="req-search" type="text" class="search-input" placeholder="Cari pegawai..." />
        </div>

        <!-- Department Filter -->
        <div class="icon-select-wrapper">
          <i data-lucide="filter" class="select-icon"></i>
          <select id="req-dept-filter" class="filter-select">
            <option value="all">Semua Dept</option>
            <option value="IT">IT</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Marketing">Marketing</option>
            <option value="Operations">Operations</option>
          </select>
        </div>

        <!-- Type Filter -->
        <div class="icon-select-wrapper">
          <i data-lucide="file-text" class="select-icon"></i>
          <select id="req-type-filter" class="filter-select">
            <option value="all">Semua Jenis</option>
            <option value="Cuti Tahunan">Cuti Tahunan</option>
            <option value="Cuti Sakit">Cuti Sakit</option>
            <option value="Cuti Menikah">Cuti Menikah</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Card Body: Tabs -->
  <div class="card-body">
    <!-- Tab Triggers -->
    <div class="tabs-list" role="tablist">
      <button class="tab-trigger active" role="tab" onclick="switchTab('pending', this)" id="tab-btn-pending">
        Pending <span class="tab-badge" id="badge-pending"><?= count($pending) ?></span>
      </button>
      <button class="tab-trigger" role="tab" onclick="switchTab('approved', this)" id="tab-btn-approved">
        Disetujui <span class="tab-badge" id="badge-approved"><?= count($approved) ?></span>
      </button>
      <button class="tab-trigger" role="tab" onclick="switchTab('rejected', this)" id="tab-btn-rejected">
        Ditolak <span class="tab-badge" id="badge-rejected"><?= count($rejected) ?></span>
      </button>
    </div>

    <!-- Tab: Pending -->
    <div class="tab-panel active" id="panel-pending">
      <div class="page-table-wrapper">
        <table class="page-table">
          <thead>
            <tr>
              <th>Pegawai</th><th>Departemen</th><th>Jenis Cuti</th>
              <th>Tanggal</th><th>Durasi</th><th>Status</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-pending">
            <?php renderRows(array_values($pending)); ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab: Approved -->
    <div class="tab-panel" id="panel-approved">
      <div class="page-table-wrapper">
        <table class="page-table">
          <thead>
            <tr>
              <th>Pegawai</th><th>Departemen</th><th>Jenis Cuti</th>
              <th>Tanggal</th><th>Durasi</th><th>Status</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-approved">
            <?php renderRows(array_values($approved)); ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab: Rejected -->
    <div class="tab-panel" id="panel-rejected">
      <div class="page-table-wrapper">
        <table class="page-table">
          <thead>
            <tr>
              <th>Pegawai</th><th>Departemen</th><th>Jenis Cuti</th>
              <th>Tanggal</th><th>Durasi</th><th>Status</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-rejected">
            <?php renderRows(array_values($rejected)); ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- ============ DETAIL MODAL ============ -->
<div id="modalDetail" class="modal-backdrop" onclick="backdropClose(event,'modalDetail')">
  <div class="modal-box modal-box-md" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Detail Permohonan Cuti</h3>
        <p class="modal-desc">Informasi lengkap permohonan cuti</p>
      </div>
      <button class="btn-icon" onclick="closeModal('modalDetail')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body" id="detailBody"><!-- filled by JS --></div>
    <div class="modal-footer" id="detailFooter"><!-- filled by JS --></div>
  </div>
</div>

<!-- ============ APPROVE MODAL ============ -->
<div id="modalApprove" class="modal-backdrop" onclick="backdropClose(event,'modalApprove')">
  <div class="modal-box modal-box-md" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Setujui Permohonan Cuti</h3>
        <p class="modal-desc">Konfirmasi persetujuan</p>
      </div>
      <button class="btn-icon" onclick="closeModal('modalApprove')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--color-gray-700);font-size:var(--font-size-sm);">
        Apakah Anda yakin ingin menyetujui permohonan cuti dari
        <strong id="approveNameLabel"></strong>?
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('modalApprove')">Batal</button>
      <button class="btn-confirm-approve" onclick="confirmApprove()">Setujui</button>
    </div>
  </div>
</div>

<!-- ============ REJECT MODAL ============ -->
<div id="modalReject" class="modal-backdrop" onclick="backdropClose(event,'modalReject')">
  <div class="modal-box modal-box-md" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Tolak Permohonan Cuti</h3>
        <p class="modal-desc">Berikan alasan penolakan</p>
      </div>
      <button class="btn-icon" onclick="closeModal('modalReject')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--color-gray-600);font-size:var(--font-size-sm);margin-bottom:var(--space-3);">
        Berikan alasan penolakan untuk permohonan cuti dari
        <strong id="rejectNameLabel"></strong>
      </p>
      <textarea id="rejectReasonInput" class="form-textarea"
                rows="4" placeholder="Masukkan alasan penolakan..."
                oninput="document.getElementById('btnRejectConfirm').disabled=!this.value.trim()"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('modalReject')">Batal</button>
      <button class="btn-confirm-reject" id="btnRejectConfirm" onclick="confirmReject()" disabled>
        Tolak Permohonan
      </button>
    </div>
  </div>
</div>

<!-- ============ JAVASCRIPT ============ -->
<script>
// ---- Tab switching ----
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-trigger').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
  applyFilter();
}

// ---- Filtering ----
document.getElementById('req-search').addEventListener('input', applyFilter);
document.getElementById('req-dept-filter').addEventListener('change', applyFilter);
document.getElementById('req-type-filter').addEventListener('change', applyFilter);

function applyFilter() {
  const q    = document.getElementById('req-search').value.toLowerCase();
  const dept = document.getElementById('req-dept-filter').value;
  const type = document.getElementById('req-type-filter').value;

  let counts = { pending:0, approved:0, rejected:0 };

  document.querySelectorAll('.req-row').forEach(row => {
    const name   = row.dataset.name   || '';
    const rdept  = row.dataset.dept   || '';
    const rtype  = row.dataset.type   || '';
    const status = row.dataset.status || '';

    const show = (!q    || name.includes(q))
              && (dept === 'all' || rdept === dept)
              && (type === 'all' || rtype === type);

    row.style.display = show ? '' : 'none';
    if (show && counts[status] !== undefined) counts[status]++;
  });

  // Update badges
  ['pending','approved','rejected'].forEach(s => {
    const el = document.getElementById('badge-' + s);
    if (el) el.textContent = counts[s];
  });
}

// ---- Modal helpers ----
function closeModal(id) {
  document.getElementById(id).classList.remove('modal-open');
}

function backdropClose(event, id) {
  if (event.target === document.getElementById(id)) closeModal(id);
}

// ---- Detail Modal ----
let _currentDetailNumId = null;

function openDetail(jsonStr) {
  const d = JSON.parse(jsonStr);
  _currentDetailNumId = d.numId; // store for approve/reject from modal

  const startDate  = new Date(d.start).toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
  const endDate    = new Date(d.end).toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
  const submitDate = new Date(d.submitted).toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
  const statusMap  = {
    pending  : '<span class="badge badge-pending">Pending</span>',
    approved : '<span class="badge badge-approved">Disetujui</span>',
    rejected : '<span class="badge badge-rejected">Ditolak</span>'
  };

  document.getElementById('detailBody').innerHTML = `
    <div class="modal-emp-row">
      <div class="avatar">
        <img src="${d.avatar}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
        <span class="avatar-fallback" style="display:none">${d.name.charAt(0)}</span>
      </div>
      <div>
        <div class="modal-emp-name">${d.name}</div>
        <div class="modal-emp-sub">${d.employeeId}</div>
        <div class="modal-emp-sub">${d.dept}</div>
      </div>
    </div>
    <div class="detail-list">
      <div class="detail-row"><div class="detail-label">Jenis Cuti</div><div class="detail-value">${d.type}</div></div>
      <div class="detail-row"><div class="detail-label">Tanggal Mulai</div><div class="detail-value">${startDate}</div></div>
      <div class="detail-row"><div class="detail-label">Tanggal Selesai</div><div class="detail-value">${endDate}</div></div>
      <div class="detail-row"><div class="detail-label">Durasi</div><div class="detail-value">${d.days} hari kerja</div></div>
      <div class="detail-row"><div class="detail-label">Alasan</div><div class="detail-value">${d.reason}</div></div>
      <div class="detail-row"><div class="detail-label">Tanggal Pengajuan</div><div class="detail-value">${submitDate}</div></div>
      <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value">${statusMap[d.status] || d.status}</div></div>
    </div>`;

  // Footer buttons (if pending)
  if (d.status === 'pending') {
    document.getElementById('detailFooter').innerHTML = `
      <button class="btn-secondary" style="color:#dc2626;" onclick="closeModal('modalDetail');openReject('${d.id}','${d.name}',null)">Tolak</button>
      <button class="btn-confirm-approve" onclick="closeModal('modalDetail');openApprove('${d.id}','${d.name}',null)">Setujui</button>`;
  } else {
    document.getElementById('detailFooter').innerHTML = '';
  }

  document.getElementById('modalDetail').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
}

// ---- Approve (AJAX) ----
let _currentApproveId = null, _currentApproveNumId = null,
    _currentApproveName = null, _currentApproveBtn = null;

function openApprove(id, name, btn) {
  _currentApproveId   = id;
  _currentApproveName = name;
  _currentApproveBtn  = btn;
  // Get numeric DB id from the row's JSON
  const row = btn ? btn.closest('tr') : null;
  _currentApproveNumId = row ? JSON.parse(row.dataset.json).numId : _currentDetailNumId;
  document.getElementById('approveNameLabel').textContent = name;
  document.getElementById('modalApprove').classList.add('modal-open');
}

async function confirmApprove() {
  closeModal('modalApprove');
  const form = new FormData();
  form.append('id',     _currentApproveNumId);
  form.append('status', 'approved');
  try {
    const res  = await fetch(EMS_API_URL + '?action=leave-status', { method:'POST', body:form });
    const json = await res.json();
    if (!json.ok) { alert('Gagal: ' + (json.error || 'Server error')); return; }
    updateRowStatus(_currentApproveId, 'approved', _currentApproveBtn);
  } catch (e) { alert('Gagal terhubung ke server.'); }
}

// ---- Reject (AJAX) ----
let _currentRejectId = null, _currentRejectNumId = null,
    _currentRejectName = null, _currentRejectBtn = null;

function openReject(id, name, btn) {
  _currentRejectId   = id;
  _currentRejectName = name;
  _currentRejectBtn  = btn;
  const row = btn ? btn.closest('tr') : null;
  _currentRejectNumId = row ? JSON.parse(row.dataset.json).numId : _currentDetailNumId;
  document.getElementById('rejectNameLabel').textContent = name;
  document.getElementById('rejectReasonInput').value = '';
  document.getElementById('btnRejectConfirm').disabled = true;
  document.getElementById('modalReject').classList.add('modal-open');
}

async function confirmReject() {
  const reason = document.getElementById('rejectReasonInput').value.trim();
  if (!reason) return;
  closeModal('modalReject');
  const form = new FormData();
  form.append('id',     _currentRejectNumId);
  form.append('status', 'rejected');
  form.append('reason', reason);
  try {
    const res  = await fetch(EMS_API_URL + '?action=leave-status', { method:'POST', body:form });
    const json = await res.json();
    if (!json.ok) { alert('Gagal: ' + (json.error || 'Server error')); return; }
    updateRowStatus(_currentRejectId, 'rejected', _currentRejectBtn);
  } catch (e) { alert('Gagal terhubung ke server.'); }
}

// ---- Update Row DOM ----
function updateRowStatus(id, newStatus, btn) {
  const allRows = document.querySelectorAll('.req-row');
  let targetRow = null;
  allRows.forEach(row => { if (JSON.parse(row.dataset.json).id === id) targetRow = row; });
  if (!targetRow) return;
  const d = JSON.parse(targetRow.dataset.json);
  d.status = newStatus;
  targetRow.dataset.status = newStatus;
  targetRow.dataset.json = JSON.stringify(d);
  const badgeCell = targetRow.querySelector('td:nth-child(6)');
  const badgeMap  = { approved:'<span class="badge badge-approved">Disetujui</span>', rejected:'<span class="badge badge-rejected">Ditolak</span>' };
  if (badgeCell) badgeCell.innerHTML = badgeMap[newStatus] || '';
  const actionGroup = targetRow.querySelector('.action-group');
  if (actionGroup) actionGroup.querySelectorAll('.btn-ghost-green,.btn-ghost-red').forEach(b => b.remove());
  const targetTbody = document.getElementById('tbody-' + newStatus);
  if (targetTbody) targetTbody.appendChild(targetRow);
  refreshBadgeCounts();
  applyFilter();
}

function refreshBadgeCounts() {
  const counts = { pending:0, approved:0, rejected:0 };
  document.querySelectorAll('.req-row').forEach(row => {
    const s = row.dataset.status;
    if (counts[s] !== undefined) counts[s]++;
  });
  ['pending','approved','rejected'].forEach(s => {
    const el = document.getElementById('badge-' + s);
    if (el) el.textContent = counts[s];
  });
}
</script>

<?php
// app/views/admin/history/index.php
$history = $history ?? [];
$year    = $year ?? (int)date('Y');

$totalCount    = count($history);
$cutiCount     = count(array_filter($history, fn($item) => $item['type'] === 'cuti'));
$lemburCount   = count(array_filter($history, fn($item) => $item['type'] === 'lembur'));
$approvedCount = count(array_filter($history, fn($item) => $item['status'] === 'approved'));
$rejectedCount = count(array_filter($history, fn($item) => $item['status'] === 'rejected'));

$historyJson = json_encode($history, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<script>window.EMS_HISTORY = <?= $historyJson ?>;</script>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
  <h2>Riwayat Pengajuan</h2>
  <p>History semua pengajuan cuti dan lembur</p>
</div>

<!-- ======== SUMMARY CARDS ======== -->
<div class="summary-grid">
  <div class="summary-card">
    <div>
      <div class="sum-label">Total Pengajuan</div>
      <div class="sum-value"><?= $totalCount ?></div>
      <div class="hist-sum-desc">Semua jenis</div>
    </div>
    <div class="hist-icon-wrap" style="background:#eff6ff;">
      <i data-lucide="file-text" style="width:24px;height:24px;color:#2563eb;"></i>
    </div>
  </div>
  <div class="summary-card">
    <div>
      <div class="sum-label">Pengajuan Cuti</div>
      <div class="sum-value"><?= $cutiCount ?></div>
      <div class="hist-sum-desc">Total cuti</div>
    </div>
    <div class="hist-icon-wrap" style="background:#e0e7ff;">
      <i data-lucide="calendar" style="width:24px;height:24px;color:#4f46e5;"></i>
    </div>
  </div>
  <div class="summary-card">
    <div>
      <div class="sum-label">Pengajuan Lembur</div>
      <div class="sum-value"><?= $lemburCount ?></div>
      <div class="hist-sum-desc">Total lembur</div>
    </div>
    <div class="hist-icon-wrap" style="background:#faf5ff;">
      <i data-lucide="clock" style="width:24px;height:24px;color:#9333ea;"></i>
    </div>
  </div>
  <div class="summary-card">
    <div>
      <div class="sum-label">Disetujui</div>
      <div class="sum-value"><?= $approvedCount ?></div>
      <div class="hist-sum-desc"><?= $rejectedCount ?> ditolak</div>
    </div>
    <div class="hist-icon-wrap" style="background:#f0fdf4;">
      <i data-lucide="file-check-2" style="width:24px;height:24px;color:#16a34a;"></i>
    </div>
  </div>
</div>

<!-- ======== TABLE CARD ======== -->
<div class="card">

  <!-- Card Header -->
  <div class="card-header">
    <div class="card-toolbar">
      <div>
        <h3 class="card-title">Riwayat Pengajuan</h3>
        <p class="result-count" id="history-result-count"><?= $totalCount ?> pengajuan ditemukan</p>
      </div>

      <div class="card-toolbar-actions">
        <!-- Search -->
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input type="text" id="history-search" class="search-input"
                 placeholder="Cari nama, ID, jenis cuti..." autocomplete="off" />
        </div>

        <!-- Type Filter -->
        <select id="history-type-filter" class="filter-select" aria-label="Filter Jenis">
          <option value="all">Semua Jenis</option>
          <option value="cuti">Cuti</option>
          <option value="lembur">Lembur</option>
        </select>

        <!-- Status Filter -->
        <select id="history-status-filter" class="filter-select" aria-label="Filter Status">
          <option value="all">Semua Status</option>
          <option value="approved">Disetujui</option>
          <option value="rejected">Ditolak</option>
        </select>

        <!-- Year Filter -->
        <select id="history-year-filter" class="filter-select" aria-label="Filter Tahun"
                onchange="window.location.href='<?= PUBLIC_URL ?>/?page=history&year='+this.value">
          <?php
            $currentYear = (int)date('Y');
            for ($y = $currentYear + 1; $y >= $currentYear - 2; $y--):
          ?>
          <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
  </div><!-- /.card-header -->

  <!-- Card Body: Table -->
  <div class="card-body" style="padding-top:0;">
    <div class="emp-table-wrapper">
      <table class="emp-table" id="history-table">
        <thead>
          <tr>
            <th>Pegawai</th>
            <th>Jenis</th>
            <th>Detail</th>
            <th>Tanggal Pengajuan</th>
            <th>Status</th>
            <th>Diproses</th>
            <th style="text-align:center;">Aksi</th>
          </tr>
        </thead>
        <tbody id="history-tbody">
          <tr>
            <td colspan="7" style="text-align:center;padding:2rem;color:var(--color-gray-400);">
              Memuat data...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div><!-- /.card-body -->

</div><!-- /.card -->


<!-- ======== DETAIL MODAL ======== -->
<div class="modal-backdrop" id="history-modal" role="dialog" aria-modal="true" aria-labelledby="history-modal-title">
  <div class="modal-box" style="max-width:500px;">

    <!-- Header -->
    <div class="modal-header">
      <div>
        <h3 class="modal-title" id="history-modal-title">Detail Riwayat Pengajuan</h3>
        <p class="modal-desc" id="h-modal-desc">Informasi lengkap pengajuan</p>
      </div>
      <button class="modal-close" id="history-modal-close" aria-label="Tutup modal">
        <i data-lucide="x"></i>
      </button>
    </div><!-- /.modal-header -->

    <!-- Body -->
    <div class="modal-body">

      <!-- Employee Identity -->
      <div class="hist-modal-profile">
        <div class="avatar" style="width:64px;height:64px;flex-shrink:0;">
          <img id="hm-avatar" alt="Avatar Pegawai"
               style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
          <span class="avatar-fallback" id="hm-avatar-fallback" style="display:none;">?</span>
        </div>
        <div style="flex:1;">
          <div style="font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);" id="hm-name">—</div>
          <div style="font-size:var(--font-size-sm);color:var(--color-gray-500);margin-top:2px;" id="hm-emp-code">—</div>
          <div style="margin-top:var(--space-2);" id="hm-type-badge"></div>
        </div>
      </div>

      <!-- Dynamic Fields (leave vs overtime) -->
      <div id="hm-dynamic-details" style="display:grid;gap:var(--space-3);margin-bottom:var(--space-4);"></div>

      <!-- Common Fields -->
      <div style="display:grid;gap:var(--space-3);">
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Alasan</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;" id="hm-reason">—</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Tanggal Pengajuan</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;" id="hm-submitted-date">—</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Diproses Oleh</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;">Admin</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 2px;">Tanggal Diproses</p>
          <p style="font-size:var(--font-size-sm);color:var(--color-gray-900);margin:0;" id="hm-processed-date">—</p>
        </div>
        <div>
          <p style="font-size:var(--font-size-xs);color:var(--color-gray-500);margin:0 0 4px;">Status</p>
          <div id="hm-status-badge"></div>
        </div>
      </div>

      <!-- Rejection Reason Box -->
      <div id="hm-rejection-box" style="display:none;margin-top:var(--space-4);padding:var(--space-3);
           background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-md);">
        <p style="color:#7f1d1d;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);margin:0 0 4px;">Alasan Penolakan:</p>
        <p style="color:#991b1b;font-size:var(--font-size-sm);margin:0;" id="hm-rejection-reason">—</p>
      </div>

    </div><!-- /.modal-body -->
  </div><!-- /.modal-box -->
</div><!-- /.modal-backdrop #history-modal -->

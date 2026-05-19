<?php
// app/views/admin/reports/index.php — Laporan & Analisis (1:1 Reports.tsx)

$selectedYear = $_GET['year'] ?? '2025';

$departmentData = [
  ['name' => 'IT',         'cuti' => 45, 'lembur' => 120],
  ['name' => 'HR',         'cuti' => 28, 'lembur' => 65],
  ['name' => 'Finance',    'cuti' => 35, 'lembur' => 95],
  ['name' => 'Marketing',  'cuti' => 42, 'lembur' => 110],
  ['name' => 'Operations', 'cuti' => 98, 'lembur' => 180],
];

$leaveTypeData = [
  ['name' => 'Cuti Tahunan',   'value' => 156],
  ['name' => 'Cuti Sakit',     'value' => 45],
  ['name' => 'Cuti Menikah',   'value' => 12],
  ['name' => 'Cuti Melahirkan','value' => 8],
  ['name' => 'Cuti Khusus',    'value' => 15],
];

$overtimeData = [
  ['name' => 'IT',         'hours' => 120],
  ['name' => 'HR',         'hours' => 65],
  ['name' => 'Finance',    'hours' => 95],
  ['name' => 'Marketing',  'hours' => 110],
  ['name' => 'Operations', 'hours' => 180],
];

$COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

$totalLeave = array_sum(array_column($leaveTypeData, 'value'));
$totalOvertimeHours = array_sum(array_column($overtimeData, 'hours'));
$totalCutiAll = array_sum(array_column($departmentData, 'cuti'));
$totalLemburAll = array_sum(array_column($departmentData, 'lembur'));
?>

<!-- ===== PAGE HEADER ===== -->
<div class="report-page-header">
  <div>
    <h2 style="font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);">
      Laporan &amp; Analisis
    </h2>
    <p style="font-size:var(--font-size-sm);color:var(--color-gray-500);margin-top:var(--space-1);">
      Laporan cuti dan lembur per departemen
    </p>
  </div>
  <div class="report-header-actions">
    <!-- Year Filter -->
    <select id="yearSelect" class="report-year-select" onchange="changeYear(this.value)">
      <option value="2026" <?= $selectedYear === '2026' ? 'selected' : '' ?>>2026</option>
      <option value="2025" <?= $selectedYear === '2025' ? 'selected' : '' ?>>2025</option>
      <option value="2024" <?= $selectedYear === '2024' ? 'selected' : '' ?>>2024</option>
      <option value="2023" <?= $selectedYear === '2023' ? 'selected' : '' ?>>2023</option>
    </select>
    <!-- Export Button -->
    <button class="btn-export" onclick="openExportModal()">
      <i data-lucide="download"></i>
      Export Laporan
    </button>
  </div>
</div>

<!-- ===== SUMMARY CARDS ===== -->
<div class="report-summary-grid">

  <div class="report-sum-card">
    <div class="report-sum-inner">
      <div>
        <p class="report-sum-label">Total Cuti</p>
        <p class="report-sum-value"><?= $totalLeave ?></p>
        <p class="report-sum-sub">Pengajuan tahun <span class="year-label"><?= htmlspecialchars($selectedYear) ?></span></p>
      </div>
      <div class="report-sum-icon sum-icon-blue">
        <i data-lucide="file-text"></i>
      </div>
    </div>
  </div>

  <div class="report-sum-card">
    <div class="report-sum-inner">
      <div>
        <p class="report-sum-label">Total Lembur</p>
        <p class="report-sum-value"><?= $totalOvertimeHours ?> jam</p>
        <p class="report-sum-sub">Tahun <span class="year-label"><?= htmlspecialchars($selectedYear) ?></span></p>
      </div>
      <div class="report-sum-icon sum-icon-purple">
        <i data-lucide="clock"></i>
      </div>
    </div>
  </div>

  <div class="report-sum-card">
    <div class="report-sum-inner">
      <div>
        <p class="report-sum-label">Departemen Aktif</p>
        <p class="report-sum-value"><?= count($departmentData) ?></p>
        <p class="report-sum-sub">Departemen terdaftar</p>
      </div>
      <div class="report-sum-icon sum-icon-green">
        <i data-lucide="building-2"></i>
      </div>
    </div>
  </div>

</div>

<!-- ===== TABS ===== -->
<div class="report-tabs-list" role="tablist">
  <button class="report-tab-trigger active" role="tab" onclick="switchReportTab('department', this)">
    Per Departemen
  </button>
  <button class="report-tab-trigger" role="tab" onclick="switchReportTab('leave-type', this)">
    Jenis Cuti
  </button>
  <button class="report-tab-trigger" role="tab" onclick="switchReportTab('overtime', this)">
    Lembur
  </button>
</div>


<!-- ===== TAB: PER DEPARTEMEN ===== -->
<div class="report-tab-panel active" id="rtab-department">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Laporan per Departemen</h3>
      <p class="card-description">Distribusi cuti dan lembur berdasarkan departemen</p>
    </div>
    <div class="card-body">
      <div class="report-chart-wrapper">
        <canvas id="deptBarChart"></canvas>
      </div>
      <!-- Detail Table -->
      <div class="report-table-wrapper">
        <table class="report-table">
          <thead>
            <tr>
              <th>Departemen</th>
              <th class="text-right">Total Cuti</th>
              <th class="text-right">Jam Lembur</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($departmentData as $dept): ?>
            <tr>
              <td><?= htmlspecialchars($dept['name']) ?></td>
              <td class="text-right"><?= (int)$dept['cuti'] ?> pengajuan</td>
              <td class="text-right"><?= (int)$dept['lembur'] ?> jam</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td class="text-right"><?= $totalCutiAll ?> pengajuan</td>
              <td class="text-right"><?= $totalLemburAll ?> jam</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>


<!-- ===== TAB: JENIS CUTI ===== -->
<div class="report-tab-panel" id="rtab-leave-type">
  <div class="report-2col">

    <!-- Pie Chart -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Distribusi Jenis Cuti</h3>
        <p class="card-description">Berdasarkan jenis cuti yang diambil</p>
      </div>
      <div class="card-body">
        <div class="report-chart-wrapper-sm">
          <canvas id="leaveTypePieChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Detail List -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Detail Jenis Cuti</h3>
        <p class="card-description">Rincian penggunaan per jenis</p>
      </div>
      <div class="card-body">
        <div class="report-detail-list">
          <?php foreach ($leaveTypeData as $i => $item): ?>
          <div class="report-detail-item">
            <div class="report-detail-left">
              <div class="color-dot" style="background-color:<?= $COLORS[$i % count($COLORS)] ?>;"></div>
              <span class="report-detail-name"><?= htmlspecialchars($item['name']) ?></span>
            </div>
            <div class="report-detail-right">
              <div class="report-detail-val"><?= (int)$item['value'] ?> pengajuan</div>
              <div class="report-detail-pct"><?= number_format($item['value'] / $totalLeave * 100, 1) ?>%</div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="report-detail-item total-row">
            <div class="report-detail-left">
              <span class="report-detail-name" style="font-weight:var(--font-weight-semibold);">Total</span>
            </div>
            <div class="report-detail-right">
              <div class="report-detail-val"><?= $totalLeave ?> pengajuan</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>


<!-- ===== TAB: LEMBUR ===== -->
<div class="report-tab-panel" id="rtab-overtime">
  <div class="report-2col">

    <!-- Pie Chart -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Distribusi Lembur per Departemen</h3>
        <p class="card-description">Total jam lembur berdasarkan departemen</p>
      </div>
      <div class="card-body">
        <div class="report-chart-wrapper-sm">
          <canvas id="overtimePieChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Detail List -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Detail Lembur</h3>
        <p class="card-description">Rincian jam lembur per departemen</p>
      </div>
      <div class="card-body">
        <div class="report-detail-list">
          <?php foreach ($overtimeData as $i => $item): ?>
          <div class="report-detail-item">
            <div class="report-detail-left">
              <div class="color-dot" style="background-color:<?= $COLORS[$i % count($COLORS)] ?>;"></div>
              <span class="report-detail-name"><?= htmlspecialchars($item['name']) ?></span>
            </div>
            <div class="report-detail-right">
              <div class="report-detail-val"><?= (int)$item['hours'] ?> jam</div>
              <div class="report-detail-pct"><?= number_format($item['hours'] / $totalOvertimeHours * 100, 1) ?>%</div>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="report-detail-item total-row">
            <div class="report-detail-left">
              <span class="report-detail-name" style="font-weight:var(--font-weight-semibold);">Total</span>
            </div>
            <div class="report-detail-right">
              <div class="report-detail-val"><?= $totalOvertimeHours ?> jam</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>


<!-- ===== EXPORT MODAL ===== -->
<div id="exportModal" class="modal-backdrop" onclick="if(event.target===this)closeExportModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Export Laporan</h3>
        <p class="modal-desc">Pilih periode laporan yang ingin diexport</p>
      </div>
      <button style="background:none;border:none;cursor:pointer;color:var(--color-gray-500);"
              onclick="closeExportModal()">
        <i data-lucide="x" style="width:20px;height:20px;"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" for="exportPeriod">Periode Laporan</label>
        <select id="exportPeriod" class="form-select" onchange="updateExportInfo()">
          <option value="">Pilih periode</option>
          <option value="harian">Laporan Harian</option>
          <option value="bulanan">Laporan Bulanan</option>
          <option value="tahunan">Laporan Tahunan</option>
        </select>
      </div>
      <div class="export-info-box">
        <p id="exportInfoText">Pilih periode untuk melihat detail</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeExportModal()">Batal</button>
      <button class="btn-primary" id="btnExportPdf" onclick="doExport()" disabled
              style="display:inline-flex;align-items:center;gap:6px;">
        <i data-lucide="download" style="width:16px;height:16px;"></i>
        Export PDF
      </button>
    </div>
  </div>
</div>


<!-- ===== CHART.JS CDN + SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
const selectedYear = document.getElementById('yearSelect').value;

// ---- Data (from PHP) ----
const deptLabels  = <?= json_encode(array_column($departmentData, 'name')) ?>;
const deptCuti    = <?= json_encode(array_column($departmentData, 'cuti')) ?>;
const deptLembur  = <?= json_encode(array_column($departmentData, 'lembur')) ?>;
const leaveLabels = <?= json_encode(array_column($leaveTypeData, 'name')) ?>;
const leaveValues = <?= json_encode(array_column($leaveTypeData, 'value')) ?>;
const otLabels    = <?= json_encode(array_column($overtimeData, 'name')) ?>;
const otHours     = <?= json_encode(array_column($overtimeData, 'hours')) ?>;

// ---- Bar Chart: Per Departemen ----
new Chart(document.getElementById('deptBarChart'), {
  type: 'bar',
  data: {
    labels: deptLabels,
    datasets: [
      {
        label: 'Total Cuti',
        data: deptCuti,
        backgroundColor: '#3B82F6',
        borderRadius: 6,
        borderSkipped: false,
      },
      {
        label: 'Jam Lembur',
        data: deptLembur,
        backgroundColor: '#8B5CF6',
        borderRadius: 6,
        borderSkipped: false,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top' },
      tooltip: { mode: 'index', intersect: false },
    },
    scales: {
      x: { grid: { color: '#f3f4f6' } },
      y: { grid: { color: '#f3f4f6' }, beginAtZero: true },
    },
  },
});

// ---- Pie Chart: Jenis Cuti ----
new Chart(document.getElementById('leaveTypePieChart'), {
  type: 'pie',
  data: {
    labels: leaveLabels,
    datasets: [{
      data: leaveValues,
      backgroundColor: COLORS,
      borderColor: '#ffffff',
      borderWidth: 2,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 12 } } },
      tooltip: {
        callbacks: {
          label: function(ctx) {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct = ((ctx.parsed / total) * 100).toFixed(1);
            return ` ${ctx.label}: ${ctx.parsed} pengajuan (${pct}%)`;
          }
        }
      }
    },
  },
});

// ---- Pie Chart: Lembur ----
new Chart(document.getElementById('overtimePieChart'), {
  type: 'pie',
  data: {
    labels: otLabels,
    datasets: [{
      data: otHours,
      backgroundColor: COLORS,
      borderColor: '#ffffff',
      borderWidth: 2,
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 12 } } },
      tooltip: {
        callbacks: {
          label: function(ctx) {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct = ((ctx.parsed / total) * 100).toFixed(1);
            return ` ${ctx.label}: ${ctx.parsed} jam (${pct}%)`;
          }
        }
      }
    },
  },
});

// ---- Tab switching ----
function switchReportTab(tab, btn) {
  document.querySelectorAll('.report-tab-trigger').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.report-tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('rtab-' + tab).classList.add('active');
}

// ---- Year change ----
function changeYear(year) {
  document.querySelectorAll('.year-label').forEach(el => el.textContent = year);
}

// ---- Export Modal ----
function openExportModal() {
  document.getElementById('exportPeriod').value = '';
  document.getElementById('exportInfoText').textContent = 'Pilih periode untuk melihat detail';
  document.getElementById('btnExportPdf').disabled = true;
  document.getElementById('exportModal').classList.add('modal-open');
  if (window.lucide) lucide.createIcons();
}

function closeExportModal() {
  document.getElementById('exportModal').classList.remove('modal-open');
}

function updateExportInfo() {
  const period = document.getElementById('exportPeriod').value;
  const year   = document.getElementById('yearSelect').value;
  const btn    = document.getElementById('btnExportPdf');
  const map = {
    harian:  'Laporan akan berisi data hari ini',
    bulanan: 'Laporan akan berisi data bulan berjalan',
    tahunan: `Laporan akan berisi data tahun ${year}`,
  };
  document.getElementById('exportInfoText').textContent = map[period] || 'Pilih periode untuk melihat detail';
  btn.disabled = !period;
}

function doExport() {
  const period = document.getElementById('exportPeriod').value;
  const year   = document.getElementById('yearSelect').value;
  if (!period) return;
  const fileName = `Laporan_${period}_${year}.pdf`;
  alert(`Mengunduh ${fileName}...`);
  closeExportModal();
}
</script>

<?php
// app/views/admin/calendar/index.php — Kalender Cuti
// Data: $year, $month, $events, $prevUrl, $nextUrl,
//       $onLeaveToday, $otThisMonth, $totalLeaveDays

$monthName = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$firstDay    = mktime(0, 0, 0, $month, 1, $year);
$startDow    = (int)date('w', $firstDay);   // 0 = Minggu
$daysInMonth = (int)date('t', $firstDay);
$todayDay    = (date('Y') == $year && date('n') == $month) ? (int)date('d') : -1;
$dayNames    = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
?>

<div class="page-header">
  <h2>Kalender Cuti</h2>
  <p>Jadwal cuti dan lembur pegawai</p>
</div>

<!-- Summary mini cards -->
<div class="summary-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:var(--space-6);">
  <div class="summary-card">
    <div class="sum-label">Sedang Cuti Hari Ini</div>
    <div class="sum-value" style="color:var(--color-primary)"><?= (int)$onLeaveToday ?></div>
  </div>
  <div class="summary-card">
    <div class="sum-label">Lembur Bulan Ini</div>
    <div class="sum-value" style="color:#d97706"><?= (int)$otThisMonth ?></div>
  </div>
  <div class="summary-card">
    <div class="sum-label">Total Hari Cuti</div>
    <div class="sum-value" style="color:var(--color-green)"><?= (int)$totalLeaveDays ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <!-- Calendar Navigation -->
    <div class="calendar-header">
      <h3><?= $monthName[$month] . ' ' . $year ?></h3>
      <div style="display:flex;gap:var(--space-2);">
        <a href="<?= htmlspecialchars($prevUrl) ?>" class="cal-nav-btn" title="Bulan sebelumnya">
          <i data-lucide="chevron-left"></i>
        </a>
        <a href="<?= htmlspecialchars($nextUrl) ?>" class="cal-nav-btn" title="Bulan berikutnya">
          <i data-lucide="chevron-right"></i>
        </a>
      </div>
    </div>
  </div>
  <div class="card-body">
    <!-- Calendar Grid -->
    <div class="calendar-grid">
      <!-- Day Names Header -->
      <?php foreach($dayNames as $dn): ?>
        <div class="cal-day-name"><?= $dn ?></div>
      <?php endforeach; ?>

      <!-- Empty cells before month start -->
      <?php for($i = 0; $i < $startDow; $i++): ?>
        <div class="cal-day other-month"></div>
      <?php endfor; ?>

      <!-- Days of month -->
      <?php for($d = 1; $d <= $daysInMonth; $d++): ?>
        <?php
          $isToday   = ($d === $todayDay);
          $dateKey   = sprintf('%04d-%02d-%02d', $year, $month, $d);
          $dayEvents = $events[$dateKey] ?? [];
        ?>
        <div class="cal-day<?= $isToday ? ' today' : '' ?>">
          <div class="cal-day-num"><?= $d ?></div>
          <?php foreach($dayEvents as $ev): ?>
            <div class="cal-event cal-event-<?= htmlspecialchars($ev[1]) ?>"
                 title="<?= htmlspecialchars($ev[0]) ?>">
              <?= htmlspecialchars($ev[0]) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>

      <!-- Fill remaining cells -->
      <?php
        $totalCells = $startDow + $daysInMonth;
        $remainder  = (7 - ($totalCells % 7)) % 7;
        for($i = 0; $i < $remainder; $i++):
      ?>
        <div class="cal-day other-month"></div>
      <?php endfor; ?>
    </div>

    <!-- Legend -->
    <div class="cal-legend">
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#dbeafe;border:1px solid #93c5fd;"></div>
        <span>Cuti (Disetujui)</span>
      </div>
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#fef3c7;border:1px solid #fcd34d;"></div>
        <span>Cuti (Pending)</span>
      </div>
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#fed7aa;border:1px solid #fb923c;"></div>
        <span>Lembur (Disetujui)</span>
      </div>
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#fce7f3;border:1px solid #f9a8d4;"></div>
        <span>Lembur (Pending)</span>
      </div>
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#fee2e2;border:1px solid #fca5a5;"></div>
        <span>Ditolak</span>
      </div>
      <div class="cal-legend-item">
        <div class="cal-legend-dot" style="background:#eff6ff;border:2px solid var(--color-primary);"></div>
        <span>Hari Ini</span>
      </div>
    </div>
  </div>
</div>

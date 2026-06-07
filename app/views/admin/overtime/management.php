<?php
// app/views/admin/overtime/management.php
// Manajemen Permohonan Lembur (Admin) — $overtimes, $counts, $totalApprHours from controller

// Normalize DB keys → view keys
$overtimes = array_map(function(array $o): array {
  return [
    'numId'      => (int)$o['id'],          // numeric DB id for API
    'id'         => $o['request_code'],      // display code (OT001)
    'employeeId' => $o['emp_code'],
    'name'       => $o['employee_name'],
    'avatar'     => !empty($o['photo_path'])
                      ? (PUBLIC_URL . $o['photo_path'] . '?v=' . time())
                      : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($o['avatar_seed'] ?? 'User'),
    'dept'       => $o['department_name'],
    'date'       => $o['overtime_date'],
    'hours'      => (float)$o['duration_hours'],
    'reason'     => $o['reason'] ?? '',
    'status'     => $o['status'],
    'submitted'  => substr($o['submitted_at'], 0, 10),
  ];
}, $overtimes ?? []);

$pending  = array_filter($overtimes, fn($o) => $o['status'] === 'pending');
$approved = array_filter($overtimes, fn($o) => $o['status'] === 'approved');
$rejected = array_filter($overtimes, fn($o) => $o['status'] === 'rejected');

$totalApprovedHours = $totalApprHours ?? array_sum(array_column(array_values($approved), 'hours'));

// Use counts from model if available
$pendingCount  = $counts['pending']  ?? count($pending);
$approvedCount = $counts['approved'] ?? count($approved);
$rejectedCount = $counts['rejected'] ?? count($rejected);

function otStatusBadge(string $status): string {
  return match($status) {
    'pending'  => '<span class="badge badge-pending">Pending</span>',
    'approved' => '<span class="badge badge-approved">Disetujui</span>',
    'rejected' => '<span class="badge badge-rejected">Ditolak</span>',
    default    => '',
  };
}

function renderOtRows(array $rows): void {
  if (empty($rows)) {
    echo '<tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--color-gray-400);">Tidak ada permohonan lembur</td></tr>';
    return;
  }
  foreach ($rows as $o):
    $json = htmlspecialchars(json_encode($o), ENT_QUOTES);
?>
    <tr class="ot-row"
        data-status="<?= htmlspecialchars($o['status']) ?>"
        data-dept="<?= htmlspecialchars($o['dept']) ?>"
        data-name="<?= htmlspecialchars(strtolower($o['name'])) ?>"
        data-json="<?= $json ?>">
      <td>
        <div class="user-cell">
          <div class="avatar avatar-sm">
            <img src="<?= htmlspecialchars($o['avatar']) ?>" alt=""
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
            <span class="avatar-fallback" style="display:none"><?= htmlspecialchars(mb_substr($o['name'],0,1)) ?></span>
          </div>
          <div class="user-cell-info">
            <div class="user-cell-name"><?= htmlspecialchars($o['name']) ?></div>
            <div class="user-cell-sub"><?= htmlspecialchars($o['employeeId']) ?></div>
          </div>
        </div>
      </td>
      <td><?= htmlspecialchars($o['dept']) ?></td>
      <td style="white-space:nowrap;">
        <div class="user-cell-name"><?= date('d M Y', strtotime($o['date'])) ?></div>
        <div class="user-cell-sub">Diajukan <?= date('d M Y', strtotime($o['submitted'])) ?></div>
      </td>
      <td><span class="badge" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;"><?= number_format($o['hours'], 1) ?> jam</span></td>
      <td style="max-width:200px;font-size:var(--font-size-xs);color:var(--color-gray-600);"><?= htmlspecialchars($o['reason']) ?></td>
      <td><?= otStatusBadge($o['status']) ?></td>
      <td>
        <div class="action-group">
          <button class="btn-icon" title="Lihat Detail" onclick="openOtDetail(this.closest('tr').dataset.json)">
            <i data-lucide="eye"></i>
          </button>
          <?php if ($o['status'] === 'pending'): ?>
          <button class="btn-icon btn-ghost-green" title="Setujui"
                  onclick="openOtApprove('<?= htmlspecialchars($o['id']) ?>', '<?= htmlspecialchars($o['name']) ?>', this)">
            <i data-lucide="check-circle"></i>
          </button>
          <button class="btn-icon btn-ghost-red" title="Tolak"
                  onclick="openOtReject('<?= htmlspecialchars($o['id']) ?>', '<?= htmlspecialchars($o['name']) ?>', this)">
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

<div class="page-header">
  <h2>Manajemen Permohonan Lembur</h2>
  <p>Kelola dan tinjau semua permohonan lembur pegawai</p>
</div>

<div class="summary-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:var(--space-6);">
  <div class="summary-card"><div class="sum-label">Total Permohonan</div><div class="sum-value"><?= count($overtimes) ?></div></div>
  <div class="summary-card"><div class="sum-label">Menunggu</div><div class="sum-value" style="color:#d97706"><?= count($pending) ?></div></div>
  <div class="summary-card"><div class="sum-label">Disetujui</div><div class="sum-value" style="color:var(--color-green)"><?= count($approved) ?></div></div>
  <div class="summary-card"><div class="sum-label">Total Jam Disetujui</div><div class="sum-value" style="color:var(--color-primary)"><?= $totalApprovedHours ?> jam</div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-toolbar">
      <div>
        <h3 class="card-title">Daftar Permohonan Lembur</h3>
        <p class="card-description">Total <?= count($overtimes) ?> permohonan</p>
      </div>
      <div class="card-toolbar-actions">
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input id="ot-search" type="text" class="search-input" placeholder="Cari pegawai..." />
        </div>
        <div class="icon-select-wrapper">
          <i data-lucide="filter" class="select-icon"></i>
          <select id="ot-dept-filter" class="filter-select">
            <option value="all">Semua Dept</option>
            <option value="IT">IT</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Marketing">Marketing</option>
            <option value="Operations">Operations</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="tabs-list" role="tablist">
      <button class="tab-trigger active" role="tab" onclick="switchOtTab('pending', this)">
        Pending <span class="tab-badge" id="ot-badge-pending"><?= count($pending) ?></span>
      </button>
      <button class="tab-trigger" role="tab" onclick="switchOtTab('approved', this)">
        Disetujui <span class="tab-badge" id="ot-badge-approved"><?= count($approved) ?></span>
      </button>
      <button class="tab-trigger" role="tab" onclick="switchOtTab('rejected', this)">
        Ditolak <span class="tab-badge" id="ot-badge-rejected"><?= count($rejected) ?></span>
      </button>
    </div>

    <?php foreach(['pending','approved','rejected'] as $tab): ?>
    <div class="tab-panel <?= $tab==='pending'?'active':'' ?>" id="ot-panel-<?= $tab ?>">
      <div class="page-table-wrapper">
        <table class="page-table">
          <thead><tr>
            <th>Pegawai</th><th>Departemen</th><th>Tanggal Lembur</th>
            <th>Durasi</th><th>Keterangan</th><th>Status</th>
            <th style="width:100px;">Aksi</th>
          </tr></thead>
          <tbody id="ot-tbody-<?= $tab ?>">
            <?php renderOtRows(array_values($$tab)); ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>


<!-- DETAIL MODAL -->
<div id="otModalDetail" class="modal-backdrop" onclick="if(event.target===this)closeOtModal('otModalDetail')">
  <div class="modal-box modal-box-md" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div><h3 class="modal-title">Detail Permohonan Lembur</h3><p class="modal-desc">Informasi lengkap permohonan lembur</p></div>
      <button class="btn-icon" onclick="closeOtModal('otModalDetail')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body" id="otDetailBody"></div>
    <div class="modal-footer" id="otDetailFooter"></div>
  </div>
</div>

<!-- APPROVE MODAL -->
<div id="otModalApprove" class="modal-backdrop" onclick="if(event.target===this)closeOtModal('otModalApprove')">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div><h3 class="modal-title">Setujui Permohonan Lembur</h3><p class="modal-desc">Konfirmasi persetujuan lembur</p></div>
      <button class="btn-icon" onclick="closeOtModal('otModalApprove')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--color-gray-700);font-size:var(--font-size-sm);">
        Apakah Anda yakin ingin menyetujui permohonan lembur dari <strong id="otApproveNameLabel"></strong>?
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeOtModal('otModalApprove')">Batal</button>
      <button class="btn-confirm-approve" onclick="confirmOtApprove()">Setujui</button>
    </div>
  </div>
</div>

<!-- REJECT MODAL -->
<div id="otModalReject" class="modal-backdrop" onclick="if(event.target===this)closeOtModal('otModalReject')">
  <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div><h3 class="modal-title">Tolak Permohonan Lembur</h3><p class="modal-desc">Berikan alasan penolakan</p></div>
      <button class="btn-icon" onclick="closeOtModal('otModalReject')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--color-gray-600);font-size:var(--font-size-sm);margin-bottom:var(--space-3);">
        Alasan penolakan untuk <strong id="otRejectNameLabel"></strong>
      </p>
      <textarea id="otRejectReasonInput" class="form-textarea" rows="4"
                placeholder="Masukkan alasan penolakan..."
                oninput="document.getElementById('btnOtRejectConfirm').disabled=!this.value.trim()"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeOtModal('otModalReject')">Batal</button>
      <button class="btn-confirm-reject" id="btnOtRejectConfirm" onclick="confirmOtReject()" disabled>Tolak Permohonan</button>
    </div>
  </div>
</div>

<script>
function switchOtTab(tab, btn) {
  document.querySelectorAll('#ot-panel-pending,#ot-panel-approved,#ot-panel-rejected').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-trigger').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('ot-panel-'+tab).classList.add('active');
  applyOtFilter();
}
document.getElementById('ot-search').addEventListener('input', applyOtFilter);
document.getElementById('ot-dept-filter').addEventListener('change', applyOtFilter);
function applyOtFilter() {
  const q=document.getElementById('ot-search').value.toLowerCase();
  const dept=document.getElementById('ot-dept-filter').value;
  const counts={pending:0,approved:0,rejected:0};
  document.querySelectorAll('.ot-row').forEach(row=>{
    const show=(!q||row.dataset.name.includes(q))&&(dept==='all'||row.dataset.dept===dept);
    row.style.display=show?'':'none';
    if(show&&counts[row.dataset.status]!==undefined) counts[row.dataset.status]++;
  });
  ['pending','approved','rejected'].forEach(s=>{const el=document.getElementById('ot-badge-'+s);if(el)el.textContent=counts[s];});
}
function closeOtModal(id){document.getElementById(id).classList.remove('modal-open');}
let _otDetailNumId = null;
function openOtDetail(jsonStr){
  const d=JSON.parse(jsonStr);
  _otDetailNumId = d.numId;
  const date=new Date(d.date).toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
  const sub=new Date(d.submitted).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
  const sm={pending:'<span class="badge badge-pending">Pending</span>',approved:'<span class="badge badge-approved">Disetujui</span>',rejected:'<span class="badge badge-rejected">Ditolak</span>'};
  document.getElementById('otDetailBody').innerHTML=`
    <div class="modal-emp-row">
      <div class="avatar"><img src="${d.avatar}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"/><span class="avatar-fallback" style="display:none">${d.name.charAt(0)}</span></div>
      <div><div class="modal-emp-name">${d.name}</div><div class="modal-emp-sub">${d.employeeId}</div><div class="modal-emp-sub">${d.dept}</div></div>
    </div>
    <div class="detail-list">
      <div class="detail-row"><div class="detail-label">Tanggal Lembur</div><div class="detail-value">${date}</div></div>
      <div class="detail-row"><div class="detail-label">Durasi</div><div class="detail-value">${d.hours} jam</div></div>
      <div class="detail-row"><div class="detail-label">Keterangan</div><div class="detail-value">${d.reason}</div></div>
      <div class="detail-row"><div class="detail-label">Tanggal Pengajuan</div><div class="detail-value">${sub}</div></div>
      <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value">${sm[d.status]||d.status}</div></div>
    </div>`;
  document.getElementById('otDetailFooter').innerHTML=d.status==='pending'
    ?`<button class="btn-secondary" style="color:#dc2626;" onclick="closeOtModal('otModalDetail');openOtReject('${d.id}','${d.name}',null)">Tolak</button>
       <button class="btn-confirm-approve" onclick="closeOtModal('otModalDetail');openOtApprove('${d.id}','${d.name}',null)">Setujui</button>` :'';
  document.getElementById('otModalDetail').classList.add('modal-open');
  if(window.lucide)lucide.createIcons();
}
let _otApproveId=null,_otApproveNumId=null,_otApproveBtn=null;
function openOtApprove(id,name,btn){
  _otApproveId=id;
  _otApproveBtn=btn;
  const row=btn?btn.closest('tr'):null;
  _otApproveNumId=row?JSON.parse(row.dataset.json).numId:_otDetailNumId;
  document.getElementById('otApproveNameLabel').textContent=name;
  document.getElementById('otModalApprove').classList.add('modal-open');
}
async function confirmOtApprove(){
  closeOtModal('otModalApprove');
  const form=new FormData();
  form.append('id',_otApproveNumId);
  form.append('status','approved');
  try{
    const res=await fetch(EMS_API_URL+'?action=ot-status',{method:'POST',body:form});
    const json=await res.json();
    if(!json.ok){alert('Gagal: '+(json.error||'Server error'));return;}
    updateOtStatus(_otApproveId,'approved',_otApproveBtn);
  }catch(e){alert('Gagal terhubung ke server.');}
}
let _otRejectId=null,_otRejectNumId=null,_otRejectBtn=null;
function openOtReject(id,name,btn){
  _otRejectId=id;
  _otRejectBtn=btn;
  const row=btn?btn.closest('tr'):null;
  _otRejectNumId=row?JSON.parse(row.dataset.json).numId:_otDetailNumId;
  document.getElementById('otRejectNameLabel').textContent=name;
  document.getElementById('otRejectReasonInput').value='';
  document.getElementById('btnOtRejectConfirm').disabled=true;
  document.getElementById('otModalReject').classList.add('modal-open');
}
async function confirmOtReject(){
  const r=document.getElementById('otRejectReasonInput').value.trim();
  if(!r)return;
  closeOtModal('otModalReject');
  const form=new FormData();
  form.append('id',_otRejectNumId);
  form.append('status','rejected');
  form.append('reason',r);
  try{
    const res=await fetch(EMS_API_URL+'?action=ot-status',{method:'POST',body:form});
    const json=await res.json();
    if(!json.ok){alert('Gagal: '+(json.error||'Server error'));return;}
    updateOtStatus(_otRejectId,'rejected',_otRejectBtn);
  }catch(e){alert('Gagal terhubung ke server.');}
}
function updateOtStatus(id,newStatus,btn){
  let tr=null;document.querySelectorAll('.ot-row').forEach(r=>{if(JSON.parse(r.dataset.json).id===id)tr=r;});
  if(!tr)return;
  const d=JSON.parse(tr.dataset.json);d.status=newStatus;tr.dataset.status=newStatus;tr.dataset.json=JSON.stringify(d);
  const bc=tr.querySelector('td:nth-child(6)');
  if(bc)bc.innerHTML={approved:'<span class="badge badge-approved">Disetujui</span>',rejected:'<span class="badge badge-rejected">Ditolak</span>'}[newStatus]||'';
  const ag=tr.querySelector('.action-group');if(ag)ag.querySelectorAll('.btn-ghost-green,.btn-ghost-red').forEach(b=>b.remove());
  const tb=document.getElementById('ot-tbody-'+newStatus);if(tb)tb.appendChild(tr);
  const counts={pending:0,approved:0,rejected:0};
  document.querySelectorAll('.ot-row').forEach(r=>{const s=r.dataset.status;if(counts[s]!==undefined)counts[s]++;});
  ['pending','approved','rejected'].forEach(s=>{const el=document.getElementById('ot-badge-'+s);if(el)el.textContent=counts[s];});
  applyOtFilter();
}
</script>

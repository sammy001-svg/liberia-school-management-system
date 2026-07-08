<?php
/**
 * Shared printable ID card view (standalone document, no app chrome).
 * Expects: $tenant, $roleLabel, $personName, $idLabel, $idValue,
 *          $fields (assoc label=>value for the detail rows), $photoUrl (optional),
 *          $validThru, $backNote (optional text for the reverse side).
 */
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$initials = strtoupper(substr($personName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ID Card — <?= htmlspecialchars($personName) ?></title>
<style>
  * { box-sizing: border-box; margin:0; padding:0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:32px; display:flex; flex-direction:column; align-items:center; gap:24px; }
  .toolbar { display:flex; gap:10px; margin-bottom:8px; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; }
  .toolbar a { padding:10px 20px; border-radius:8px; border:1px solid #d1d5db; font-weight:600; font-size:13px; cursor:pointer; background:#fff; color:#374151; text-decoration:none; }

  .card {
    width: 3.375in; height: 2.125in;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    overflow: hidden;
    position: relative;
    display:flex; flex-direction:column;
  }
  .card-band { height: 0.5in; background: linear-gradient(120deg, <?= htmlspecialchars($primary) ?>, <?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; gap:8px; padding:0 12px; color:#fff; }
  .card-band .logo { width:28px; height:28px; border-radius:6px; background:rgba(255,255,255,0.25); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; flex-shrink:0; overflow:hidden; }
  .card-band .logo img { width:100%; height:100%; object-fit:cover; }
  .card-band .school-name { font-size:11px; font-weight:800; line-height:1.2; }
  .card-band .role-tag { margin-left:auto; font-size:8.5px; font-weight:700; letter-spacing:0.06em; background:rgba(255,255,255,0.22); padding:3px 8px; border-radius:20px; }

  .card-body { flex:1; display:flex; gap:10px; padding:10px 12px; }
  .card-photo { width:0.85in; height:0.85in; border-radius:8px; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-size:26px; font-weight:800; flex-shrink:0; overflow:hidden; }
  .card-photo img { width:100%; height:100%; object-fit:cover; }
  .card-info { flex:1; min-width:0; }
  .card-name { font-size:13px; font-weight:800; color:#111827; line-height:1.2; }
  .card-idno { font-size:10px; font-family:'Courier New',monospace; letter-spacing:0.03em; color:#4b5563; margin-top:2px; }
  .card-fields { margin-top:6px; display:grid; grid-template-columns:1fr; gap:1px; }
  .card-fields div { font-size:8.5px; color:#374151; display:flex; gap:4px; }
  .card-fields b { color:#6b7280; font-weight:700; min-width:52px; display:inline-block; }

  .card-footer { border-top:1px dashed #e5e7eb; padding:5px 12px; display:flex; justify-content:space-between; align-items:center; }
  .card-footer .valid { font-size:7.5px; color:#9ca3af; }
  .card-footer .sig { font-size:7.5px; color:#374151; text-align:right; }
  .card-footer .sig .line { border-top:1px solid #9ca3af; margin-top:12px; padding-top:2px; width:80px; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .card { box-shadow:none; }
    @page { size: 3.375in 2.125in; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Print / Save as PDF</button>
  <a href="javascript:void(0)" onclick="window.close()">Close</a>
</div>

<div class="card">
  <div class="card-band">
    <div class="logo"><?php if(!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(strtoupper(substr($schoolName,0,1))) ?><?php endif; ?></div>
    <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
    <div class="role-tag"><?= htmlspecialchars(strtoupper($roleLabel)) ?> ID</div>
  </div>
  <div class="card-body">
    <div class="card-photo"><?php if(!empty($photoUrl)): ?><img src="<?= htmlspecialchars($photoUrl) ?>" alt=""><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></div>
    <div class="card-info">
      <div class="card-name"><?= htmlspecialchars($personName) ?></div>
      <div class="card-idno"><?= htmlspecialchars($idLabel) ?>: <?= htmlspecialchars($idValue) ?></div>
      <div class="card-fields">
        <?php foreach($fields as $label => $value): if($value === null || $value === '') continue; ?>
        <div><b><?= htmlspecialchars($label) ?>:</b> <?= htmlspecialchars($value) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="card-footer">
    <div class="valid">Valid thru <?= htmlspecialchars($validThru) ?></div>
    <div class="sig"><div class="line">Authorized Signature</div></div>
  </div>
</div>

<?php if (!empty($backNote)): ?>
<div class="card">
  <div class="card-body" style="padding:14px;align-items:center;">
    <div style="font-size:8.5px;color:#374151;line-height:1.6;">
      <div style="font-weight:800;font-size:9.5px;color:#111827;margin-bottom:6px;"><?= htmlspecialchars($schoolName) ?></div>
      <?= nl2br(htmlspecialchars($backNote)) ?>
    </div>
  </div>
  <div class="card-footer">
    <div class="valid">If found, please return to the school office.</div>
  </div>
</div>
<?php endif; ?>

</body>
</html>

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
// A short, deterministic-looking "barcode" purely for visual authenticity — encodes nothing.
$barcodeSeed = crc32($idValue . $personName);
mt_srand($barcodeSeed);
$barcodeBars = [];
for ($i = 0; $i < 38; $i++) { $barcodeBars[] = mt_rand(1, 3); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ID Card — <?= htmlspecialchars($personName) ?></title>
<style>
  * { box-sizing: border-box; margin:0; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background:#eef0f3; padding:40px 32px;
    display:flex; flex-direction:column; align-items:center; gap:22px;
  }
  .toolbar { display:flex; gap:10px; margin-bottom:4px; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.15); }
  .toolbar a { padding:10px 20px; border-radius:8px; border:1px solid #d1d5db; font-weight:600; font-size:13px; cursor:pointer; background:#fff; color:#374151; text-decoration:none; }
  .side-label { font-size:10px; font-weight:700; letter-spacing:0.12em; color:#9ca3af; text-transform:uppercase; margin-bottom:-8px; }

  .card {
    width: 3.375in; height: 2.125in;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06), 0 12px 28px rgba(15,23,42,0.16);
    overflow: hidden;
    position: relative;
    display:flex; flex-direction:column;
  }

  /* ── FRONT ── */
  .card-band {
    height: 0.56in;
    background: linear-gradient(115deg, <?= htmlspecialchars($primary) ?> 0%, <?= htmlspecialchars($secondary) ?> 100%);
    position: relative;
    display:flex; align-items:center; gap:8px; padding:0 12px;
    color:#fff;
    overflow:hidden;
  }
  .card-band::before {
    /* subtle diagonal texture for a less flat, more "printed" look */
    content:''; position:absolute; inset:0;
    background-image: repeating-linear-gradient(120deg, rgba(255,255,255,0.07) 0 2px, transparent 2px 14px);
    pointer-events:none;
  }
  .card-band::after {
    content:''; position:absolute; right:-30px; top:-30px; width:110px; height:110px; border-radius:50%;
    background:rgba(255,255,255,0.10); pointer-events:none;
  }
  .punch-hole { position:absolute; top:7px; left:50%; transform:translateX(-50%); width:34px; height:5px; border-radius:4px; background:rgba(0,0,0,0.14); z-index:3; }
  .card-band .logo { width:26px; height:26px; border-radius:7px; background:rgba(255,255,255,0.92); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; color:<?= htmlspecialchars($primary) ?>; flex-shrink:0; overflow:hidden; z-index:1; box-shadow:0 1px 3px rgba(0,0,0,0.2); }
  .card-band .logo img { width:100%; height:100%; object-fit:cover; }
  .card-band .school-text { z-index:1; line-height:1.15; min-width:0; }
  .card-band .school-name { font-size:10.5px; font-weight:800; letter-spacing:0.01em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:1.7in; }
  .card-band .school-tagline { font-size:6.6px; font-weight:600; letter-spacing:0.09em; opacity:0.88; text-transform:uppercase; margin-top:1px; }
  .card-band .role-tag {
    margin-left:auto; z-index:1;
    font-size:7.5px; font-weight:800; letter-spacing:0.07em; text-transform:uppercase;
    background:rgba(255,255,255,0.94); color:<?= htmlspecialchars($primary) ?>;
    padding:4px 8px; border-radius:20px; white-space:nowrap;
  }

  .card-body { flex:1; display:flex; gap:10px; padding:10px 12px 4px; position:relative; }
  .card-photo {
    width:0.82in; height:0.82in; border-radius:10px; flex-shrink:0; overflow:hidden;
    background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>);
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:800;
    border:2px solid #fff; box-shadow:0 0 0 1.5px <?= htmlspecialchars($primary) ?>33, 0 3px 8px rgba(0,0,0,0.12);
  }
  .card-photo img { width:100%; height:100%; object-fit:cover; }

  .card-info { flex:1; min-width:0; display:flex; flex-direction:column; }
  .card-name { font-size:12.5px; font-weight:800; color:#0f172a; line-height:1.18; letter-spacing:0.01em; }
  .card-idchip {
    display:inline-block; margin-top:3px; font-size:8.5px; font-family:'Courier New',monospace; font-weight:700;
    letter-spacing:0.02em; color:<?= htmlspecialchars($secondary) ?>; background:<?= htmlspecialchars($primary) ?>14;
    border:1px solid <?= htmlspecialchars($primary) ?>3a; border-radius:5px; padding:2px 6px;
  }
  .card-fields { margin-top:5px; display:grid; grid-template-columns:1fr 1fr; gap:2.5px 6px; }
  .card-fields div { font-size:7.6px; color:#374151; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .card-fields b { display:block; color:#9ca3af; font-weight:700; font-size:6.4px; letter-spacing:0.05em; text-transform:uppercase; }

  .card-footer { border-top:1px solid #f1f5f9; padding:5px 12px 7px; display:flex; justify-content:space-between; align-items:flex-end; gap:8px; }
  .barcode { display:flex; align-items:flex-end; gap:0.9px; height:16px; }
  .barcode span { display:block; width:1.1px; background:#111827; }
  .card-footer .valid { font-size:6.6px; color:#9ca3af; font-weight:600; }
  .card-footer .valid b { color:#4b5563; }
  .card-footer .sig { font-size:6.8px; color:#374151; text-align:right; flex-shrink:0; }
  .card-footer .sig .line { border-top:1px solid #cbd5e1; margin-top:11px; padding-top:2px; width:74px; }

  /* ── BACK ── */
  .card.back { align-items:stretch; }
  .back-watermark {
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    font-size:1.55in; font-weight:800; color:<?= htmlspecialchars($primary) ?>; opacity:0.045;
    pointer-events:none; user-select:none; letter-spacing:-0.02em;
  }
  .back-top { height:0.16in; background: linear-gradient(115deg, <?= htmlspecialchars($primary) ?> 0%, <?= htmlspecialchars($secondary) ?> 100%); flex-shrink:0; }
  .back-content { flex:1; padding:12px 14px 6px; position:relative; display:flex; flex-direction:column; }
  .back-title { font-weight:800; font-size:9px; color:#0f172a; margin-bottom:5px; letter-spacing:0.01em; }
  .back-note { font-size:7.6px; color:#4b5563; line-height:1.55; position:relative; z-index:1; }
  .back-note b { color:#111827; }
  .back-terms { margin-top:auto; padding-top:6px; border-top:1px dashed #e2e8f0; font-size:6.4px; color:#9ca3af; line-height:1.5; position:relative; z-index:1; }
  .back-footer {
    border-top:1px solid #f1f5f9; padding:5px 14px 7px; display:flex; justify-content:space-between; align-items:center;
    font-size:6.6px; color:#9ca3af; font-weight:600;
  }
  .back-footer .contact { color:<?= htmlspecialchars($secondary) ?>; font-weight:700; }

  @media print {
    body { background:#fff; padding:0; gap:0.15in; }
    .toolbar, .side-label { display:none; }
    .card { box-shadow:none; border:1px solid #e5e7eb; }
    @page { size: 3.375in 2.125in; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Print / Save as PDF</button>
  <a href="javascript:void(0)" onclick="window.close()">Close</a>
</div>

<div class="side-label">Front</div>
<div class="card front">
  <div class="punch-hole"></div>
  <div class="card-band">
    <div class="logo"><?php if(!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(strtoupper(substr($schoolName,0,1))) ?><?php endif; ?></div>
    <div class="school-text">
      <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
      <div class="school-tagline"><?= htmlspecialchars($roleLabel) ?> Identification Card</div>
    </div>
    <div class="role-tag"><?= htmlspecialchars(strtoupper($roleLabel)) ?></div>
  </div>
  <div class="card-body">
    <div class="card-photo"><?php if(!empty($photoUrl)): ?><img src="<?= htmlspecialchars($photoUrl) ?>" alt=""><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?></div>
    <div class="card-info">
      <div class="card-name"><?= htmlspecialchars($personName) ?></div>
      <div class="card-idchip"><?= htmlspecialchars(strtoupper($idLabel)) ?>: <?= htmlspecialchars($idValue) ?></div>
      <div class="card-fields">
        <?php foreach($fields as $label => $value): if($value === null || $value === '') continue; ?>
        <div><b><?= htmlspecialchars($label) ?></b><?= htmlspecialchars($value) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="card-footer">
    <div class="barcode"><?php foreach($barcodeBars as $w): ?><span style="width:<?= $w ?>px;height:<?= $w===1?'10px':($w===2?'14px':'16px') ?>;"></span><?php endforeach; ?></div>
    <div class="valid">Valid thru<br><b><?= htmlspecialchars($validThru) ?></b></div>
    <div class="sig"><div class="line">Authorized Signature</div></div>
  </div>
</div>

<?php if (!empty($backNote)): ?>
<div class="side-label">Back</div>
<div class="card back">
  <div class="back-top"></div>
  <div class="back-content">
    <div class="back-watermark"><?= htmlspecialchars($initials) ?></div>
    <div class="back-title">Terms of Use</div>
    <div class="back-note"><?= nl2br(htmlspecialchars($backNote)) ?></div>
    <div class="back-terms">This card remains the property of <?= htmlspecialchars($schoolName) ?> and must be surrendered upon request. Unauthorized use, alteration, or transfer of this card is prohibited.</div>
  </div>
  <div class="back-footer">
    <span>If found, please return to the school office.</span>
    <span class="contact"><?= htmlspecialchars($tenant['phone'] ?? $tenant['email'] ?? '') ?></span>
  </div>
</div>
<?php endif; ?>

</body>
</html>

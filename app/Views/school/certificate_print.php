<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$typeLabels = ['completion' => 'Certificate of Completion', 'promotion' => 'Certificate of Promotion', 'graduation' => 'Certificate of Graduation', 'achievement' => 'Certificate of Achievement'];
$certTitle = $cert['title'] ?: ($typeLabels[$cert['type']] ?? 'Certificate of Completion');
$bodyVerb = ['completion' => 'has successfully completed', 'promotion' => 'has been promoted upon completion of', 'graduation' => 'has graduated upon completion of', 'achievement' => 'is recognized for outstanding achievement during'][$cert['type']] ?? 'has successfully completed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($certTitle) ?> — <?= htmlspecialchars($cert['student_name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:11.69in; max-width:100%; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; margin-left:auto; }

  .sheet { width:11.69in; height:8.27in; background:#fdfcf8; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.4in; position:relative; }

  .border-outer { position:absolute; inset:0.25in; border:3px solid <?= htmlspecialchars($primary) ?>; }
  .border-inner { position:absolute; inset:0.34in; border:1px solid <?= htmlspecialchars($secondary) ?>; }
  .corner { position:absolute; width:34px; height:34px; border:2px solid <?= htmlspecialchars($primary) ?>; }
  .corner.tl { top:0.2in; left:0.2in; border-right:none; border-bottom:none; }
  .corner.tr { top:0.2in; right:0.2in; border-left:none; border-bottom:none; }
  .corner.bl { bottom:0.2in; left:0.2in; border-right:none; border-top:none; }
  .corner.br { bottom:0.2in; right:0.2in; border-left:none; border-top:none; }

  .content { position:relative; z-index:1; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:0 0.7in; }

  .crest { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:26px; margin-bottom:10px; box-shadow:0 4px 10px rgba(0,0,0,0.15); overflow:hidden; }
  .crest img { width:100%; height:100%; object-fit:cover; }

  .school-name { font-size:15px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#111827; }
  .school-sub { font-size:10px; color:#6b7280; margin-top:2px; letter-spacing:0.03em; }

  .cert-title { font-family:'Georgia','Times New Roman',serif; font-size:34px; font-weight:700; color:<?= htmlspecialchars($primary) ?>; margin:18px 0 4px; letter-spacing:0.02em; }
  .cert-subtitle { font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:#9ca3af; margin-bottom:22px; }

  .presented-to { font-size:11px; letter-spacing:0.15em; text-transform:uppercase; color:#6b7280; }
  .student-name { font-family:'Georgia','Times New Roman',serif; font-style:italic; font-size:32px; font-weight:700; color:#111827; margin:8px 0 14px; padding-bottom:10px; border-bottom:1.5px solid <?= htmlspecialchars($secondary) ?>; display:inline-block; min-width:4in; }

  .cert-body { font-size:13.5px; color:#374151; line-height:1.8; max-width:7in; }
  .cert-body strong { color:#111827; }

  .meta-row { display:flex; justify-content:center; gap:60px; margin-top:22px; font-size:10.5px; color:#6b7280; }
  .meta-row .meta-label { text-transform:uppercase; letter-spacing:0.05em; font-size:9px; color:#9ca3af; }
  .meta-row .meta-value { font-family:monospace; font-size:12px; color:#111827; font-weight:700; margin-top:2px; }

  .signatures { display:flex; justify-content:center; gap:110px; margin-top:34px; }
  .signatures div { text-align:center; font-size:10.5px; color:#374151; }
  .signatures .line { width:2.2in; border-top:1px solid #9ca3af; margin-bottom:6px; padding-top:30px; }

  .doc-footer { position:absolute; bottom:0.45in; left:0; right:0; text-align:center; font-size:8.5px; color:#b0b5bd; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; }
    @page { size: A4 landscape; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Print / Save as PDF</button>
</div>

<div class="sheet">
  <div class="border-outer"></div>
  <div class="border-inner"></div>
  <div class="corner tl"></div>
  <div class="corner tr"></div>
  <div class="corner bl"></div>
  <div class="corner br"></div>

  <div class="content">
    <div class="crest"><?php if(!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(strtoupper(substr($schoolName,0,1))) ?><?php endif; ?></div>
    <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
    <div class="school-sub"><?= htmlspecialchars(trim(implode(', ', array_filter([$tenant['address'] ?? null, $tenant['city'] ?? null, $tenant['country'] ?? null])))) ?></div>

    <div class="cert-title"><?= htmlspecialchars($certTitle) ?></div>
    <div class="cert-subtitle">This certificate is proudly presented to</div>

    <div class="student-name"><?= htmlspecialchars($cert['student_name']) ?></div>

    <div class="cert-body">
      who <?= htmlspecialchars($bodyVerb) ?> the <strong><?= htmlspecialchars($cert['year_name'] ?? 'academic year') ?></strong> academic year
      <?php if($cert['class_name']): ?>in <strong><?= htmlspecialchars($cert['class_name']) ?></strong><?php endif; ?>
      at <strong><?= htmlspecialchars($schoolName) ?></strong>, demonstrating consistent effort, dedication, and achievement throughout the year.
      <?php if($cert['remarks']): ?><br><em><?= htmlspecialchars($cert['remarks']) ?></em><?php endif; ?>
    </div>

    <div class="meta-row">
      <div><div class="meta-label">Certificate No.</div><div class="meta-value"><?= htmlspecialchars($cert['certificate_no']) ?></div></div>
      <div><div class="meta-label">Date Issued</div><div class="meta-value"><?= date('d M Y', strtotime($cert['issued_date'])) ?></div></div>
      <div><div class="meta-label">Admission No.</div><div class="meta-value"><?= htmlspecialchars($cert['admission_no']) ?></div></div>
    </div>

    <div class="signatures">
      <div><div class="line">Class Teacher / Registrar</div></div>
      <div><div class="line">Principal</div></div>
    </div>
  </div>

  <div class="doc-footer">This is an official certificate issued by <?= htmlspecialchars($schoolName) ?> · Generated <?= date('F d, Y') ?></div>
</div>

</body>
</html>

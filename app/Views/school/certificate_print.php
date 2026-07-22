<?php
$cfg = require ROOT_DIR . '/config/app.php';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$certTitle = $cert['title'] ?: ('Certificate of '.$cert['type_name']);
// Student certificates (tied to an academic year) keep the original "completed the
// academic year" phrasing; certificates with no academic year (typically staff/teacher
// recognitions) get a generic phrasing built around the type name instead.
$hasYear = !empty($cert['academic_year_id']);

// CELDI Academy gets its own designed look (fixed navy/gold palette + seal watermark)
// instead of the generic theme-colored layout every other school on the platform uses —
// matched by name rather than a settings flag since this is a one-off for this tenant,
// not a template choice meant to be exposed to schools generally.
$isCeldi = stripos($schoolName, 'CELDI') !== false;
$primary   = $isCeldi ? '#1B2A4A' : ($tenant['primary_color'] ?? '#10B981');
$secondary = $isCeldi ? '#C9A227' : ($tenant['secondary_color'] ?? '#059669');
$sheetBg   = $isCeldi ? '#fffdf6' : '#fdfcf8';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($certTitle) ?> — <?= htmlspecialchars($cert['recipient_name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:11.69in; max-width:100%; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; margin-left:auto; }

  .sheet { width:11.69in; height:8.27in; background:<?= htmlspecialchars($sheetBg) ?>; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.4in; position:relative; overflow:hidden; }

  .border-outer { position:absolute; inset:0.25in; border:<?= $isCeldi ? '4px double' : '3px solid' ?> <?= htmlspecialchars($primary) ?>; }
  .border-inner { position:absolute; inset:0.34in; border:1px solid <?= htmlspecialchars($secondary) ?>; }
  .corner { position:absolute; width:34px; height:34px; border:2px solid <?= htmlspecialchars($primary) ?>; }
  .corner.tl { top:0.2in; left:0.2in; border-right:none; border-bottom:none; }
  .corner.tr { top:0.2in; right:0.2in; border-left:none; border-bottom:none; }
  .corner.bl { bottom:0.2in; left:0.2in; border-right:none; border-top:none; }
  .corner.br { bottom:0.2in; right:0.2in; border-left:none; border-top:none; }

  <?php if($isCeldi): ?>
  /* Faint central seal watermark behind the content — the "customized" CELDI signature. */
  .watermark { position:absolute; top:50%; left:50%; width:5in; height:5in; transform:translate(-50%,-50%); border-radius:50%; border:14px solid <?= htmlspecialchars($secondary) ?>; opacity:0.05; z-index:0; }
  <?php endif; ?>

  .content { position:relative; z-index:1; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:0 0.7in; }

  .crest { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:26px; margin-bottom:10px; box-shadow:0 4px 10px rgba(0,0,0,0.15); overflow:hidden; <?= $isCeldi ? 'border:2px solid '.htmlspecialchars($secondary).';' : '' ?> }
  .crest img { width:100%; height:100%; object-fit:cover; }

  .school-name { font-size:15px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:<?= $isCeldi ? htmlspecialchars($primary) : '#111827' ?>; }
  .school-sub { font-size:10px; color:#6b7280; margin-top:2px; letter-spacing:0.03em; }

  .placement-ribbon { display:inline-block; margin-top:10px; padding:5px 18px; border-radius:20px; font-size:11px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#fff; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); box-shadow:0 3px 8px rgba(0,0,0,0.18); }

  .cert-title { font-family:'Georgia','Times New Roman',serif; font-size:34px; font-weight:700; color:<?= htmlspecialchars($primary) ?>; margin:18px 0 4px; letter-spacing:0.02em; <?= $isCeldi ? 'font-variant:small-caps;' : '' ?> }
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
  <?php if($isCeldi): ?><div class="watermark"></div><?php endif; ?>

  <div class="content">
    <div class="crest"><?php if(!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(strtoupper(substr($schoolName,0,1))) ?><?php endif; ?></div>
    <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
    <div class="school-sub"><?= htmlspecialchars(trim(implode(', ', array_filter([$tenant['address'] ?? null, $tenant['city'] ?? null, $tenant['country'] ?? null])))) ?></div>

    <div class="cert-title"><?= htmlspecialchars($certTitle) ?></div>
    <div class="cert-subtitle">This certificate is proudly presented to</div>

    <div class="student-name"><?= htmlspecialchars($cert['recipient_name']) ?></div>
    <?php if(!empty($cert['placement'])): ?><div class="placement-ribbon">🏅 <?= htmlspecialchars($cert['placement']) ?> Place</div><?php endif; ?>

    <div class="cert-body" style="margin-top:<?= !empty($cert['placement']) ? '14px' : '0' ?>;">
      <?php if($hasYear): ?>
        who has successfully completed the <strong><?= htmlspecialchars($cert['year_name'] ?? 'academic year') ?></strong> academic year
        <?php if($cert['class_name']): ?>in <strong><?= htmlspecialchars($cert['class_name']) ?></strong><?php endif; ?>
        at <strong><?= htmlspecialchars($schoolName) ?></strong>, demonstrating consistent effort, dedication, and achievement throughout the year.
      <?php else: ?>
        is recognized by <strong><?= htmlspecialchars($schoolName) ?></strong> in respect of <strong><?= htmlspecialchars($cert['type_name']) ?></strong>.
      <?php endif; ?>
      <?php if($cert['remarks']): ?><br><em><?= htmlspecialchars($cert['remarks']) ?></em><?php endif; ?>
    </div>

    <div class="meta-row">
      <div><div class="meta-label">Certificate No.</div><div class="meta-value"><?= htmlspecialchars($cert['certificate_no']) ?></div></div>
      <div><div class="meta-label">Date Issued</div><div class="meta-value"><?= date('d M Y', strtotime($cert['issued_date'])) ?></div></div>
      <?php if(!empty($cert['admission_no'])): ?>
      <div><div class="meta-label">Admission No.</div><div class="meta-value"><?= htmlspecialchars($cert['admission_no']) ?></div></div>
      <?php endif; ?>
      <?php if(!empty($cert['placement'])): ?>
      <div><div class="meta-label">Placement</div><div class="meta-value"><?= htmlspecialchars($cert['placement']) ?></div></div>
      <?php endif; ?>
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

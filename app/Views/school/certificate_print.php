<?php
$cfg = require ROOT_DIR . '/config/app.php';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');

// Fixed award palette — deliberately independent of the tenant's UI theme colours,
// because this is a designed certificate rather than a themed document.
$GOLD_LIGHT = '#F7E5A3';
$GOLD_MID   = '#D9B451';
$GOLD_DEEP  = '#A97C1B';
$RED_LIGHT  = '#D6202F';
$RED_DEEP   = '#8E0F1C';
$INK        = '#1E1D1D';

// "CERTIFICATE" / "OF ACHIEVEMENT" — the second line follows the certificate's type.
$typeLine = 'OF ' . strtoupper($cert['type_name'] ?? 'ACHIEVEMENT');

// Student certificates (tied to an academic year) keep the "completed the academic year"
// phrasing; those without one (typically staff/teacher recognitions) get generic wording.
$hasYear = !empty($cert['academic_year_id']);
if (!empty($cert['remarks'])) {
    $citation = $cert['remarks'];
} elseif ($hasYear) {
    $citation = 'This is to certify that the above-named has successfully completed the '
        . ($cert['year_name'] ?? '') . ' academic year'
        . (!empty($cert['class_name']) ? ' in ' . $cert['class_name'] : '')
        . ' at ' . $schoolName . ', and is hereby awarded this certificate in recognition of '
        . 'diligence, good conduct and academic achievement throughout the year.';
} else {
    $citation = 'This is to certify that the above-named is hereby recognised by ' . $schoolName
        . ' in respect of ' . ($cert['type_name'] ?? 'outstanding service')
        . ', in acknowledgement of dedication, commitment and valued contribution to the school community.';
}

// The rosette carries the year on top and the placement beneath it when one was
// recorded (1st / 2nd / 3rd), falling back to "AWARD" exactly like the design.
$sealTop    = $cert['year_name'] ?: ($cert['issued_date'] ? date('Y', strtotime($cert['issued_date'])) : date('Y'));
$sealBottom = $cert['placement'] ? strtoupper($cert['placement']) : 'AWARD';

$subLine = trim(implode('  ·  ', array_filter([
    strtoupper($schoolName),
    !empty($cert['class_name']) ? strtoupper($cert['class_name']) : null,
    !empty($cert['admission_no']) ? 'ADM ' . $cert['admission_no'] : null,
])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($cert['title'] ?: 'Certificate') ?> — <?= htmlspecialchars($cert['recipient_name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body {
    font-family:'Segoe UI', Arial, sans-serif; background:#d8d5d0; padding:28px;
    display:flex; flex-direction:column; align-items:center;
    -webkit-print-color-adjust:exact; print-color-adjust:exact;
  }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:11.69in; max-width:100%; }
  .toolbar a { font-size:13px; color:#374151; text-decoration:none; }
  .toolbar button {
    padding:10px 22px; border-radius:8px; border:none; font-weight:600; font-size:13px;
    cursor:pointer; background:<?= $INK ?>; color:<?= $GOLD_LIGHT ?>; margin-left:auto;
  }

  .sheet {
    width:11.69in; height:8.27in; background:<?= $INK ?>;
    box-shadow:0 10px 30px rgba(0,0,0,0.35); position:relative; overflow:hidden;
    -webkit-print-color-adjust:exact; print-color-adjust:exact;
  }

  /* ── Left decorative panel: red wedge, gold stripe through it, thin gold edge ── */
  .deco { position:absolute; inset:0; }
  .deco > div { position:absolute; inset:0; }
  .deco-red {
    background:linear-gradient(160deg, <?= $RED_LIGHT ?> 0%, <?= $RED_DEEP ?> 100%);
    -webkit-clip-path:polygon(0 0, 34% 0, 9% 100%, 0 100%);
            clip-path:polygon(0 0, 34% 0, 9% 100%, 0 100%);
  }
  .deco-gold-band {
    background:linear-gradient(160deg, <?= $GOLD_LIGHT ?> 0%, <?= $GOLD_MID ?> 45%, <?= $GOLD_DEEP ?> 100%);
    -webkit-clip-path:polygon(21% 0, 27.5% 0, 2.5% 100%, -4% 100%);
            clip-path:polygon(21% 0, 27.5% 0, 2.5% 100%, -4% 100%);
  }
  .deco-gold-edge {
    background:linear-gradient(160deg, <?= $GOLD_LIGHT ?> 0%, <?= $GOLD_MID ?> 50%, <?= $GOLD_DEEP ?> 100%);
    -webkit-clip-path:polygon(34% 0, 35.4% 0, 10.4% 100%, 9% 100%);
            clip-path:polygon(34% 0, 35.4% 0, 10.4% 100%, 9% 100%);
  }

  /* ── Diamond badge with rosette seal ── */
  .badge { position:absolute; left:9.5%; top:50%; width:2.15in; height:2.15in; transform:translateY(-50%) rotate(45deg);
           background:<?= $INK ?>; border:2px solid <?= $GOLD_MID ?>; }
  .badge-inner { position:absolute; inset:0; transform:rotate(-45deg); display:flex; align-items:center; justify-content:center; }

  .seal-wrap { position:relative; width:1.15in; height:1.45in; display:flex; justify-content:center; }
  .ribbon { position:absolute; top:0.72in; width:0.26in; height:0.68in;
            background:linear-gradient(180deg, <?= $GOLD_MID ?>, <?= $GOLD_DEEP ?>);
            -webkit-clip-path:polygon(0 0, 100% 0, 100% 100%, 50% 78%, 0 100%);
                    clip-path:polygon(0 0, 100% 0, 100% 100%, 50% 78%, 0 100%); }
  .ribbon.l { left:0.18in; transform:rotate(11deg); }
  .ribbon.r { right:0.18in; transform:rotate(-11deg); }

  .seal-outer {
    position:absolute; top:0; width:1.15in; height:1.15in; border-radius:50%;
    background:repeating-conic-gradient(<?= $GOLD_LIGHT ?> 0deg 7deg, <?= $GOLD_DEEP ?> 7deg 14deg);
    display:flex; align-items:center; justify-content:center;
  }
  .seal-ring { width:0.99in; height:0.99in; border-radius:50%;
               background:linear-gradient(145deg, <?= $GOLD_LIGHT ?>, <?= $GOLD_MID ?> 50%, <?= $GOLD_DEEP ?>);
               display:flex; align-items:center; justify-content:center; }
  .seal-core {
    width:0.84in; height:0.84in; border-radius:50%; border:1.5px solid <?= $GOLD_MID ?>;
    background:radial-gradient(circle at 35% 30%, #2C3E63 0%, #16223C 70%);
    display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;
  }
  .seal-core .top { font-size:12px; font-weight:800; letter-spacing:0.06em; color:<?= $GOLD_LIGHT ?>; line-height:1; }
  .seal-core .rule { width:0.44in; height:1px; background:<?= $GOLD_MID ?>; margin:3px 0; }
  .seal-core .bot { font-size:8.5px; font-weight:800; letter-spacing:0.1em; color:<?= $GOLD_LIGHT ?>; line-height:1.1; padding:0 3px; }

  /* Optional school logo, small, top-left of the dark field */
  .school-mark { position:absolute; right:0.55in; top:0.5in; display:flex; align-items:center; gap:9px; }
  .school-mark img { width:40px; height:40px; object-fit:contain; }
  .school-mark span { font-size:9.5px; letter-spacing:0.16em; color:#8A8A8A; text-transform:uppercase; }

  /* ── Main content ── */
  .content { position:absolute; left:40%; right:0.7in; top:0.9in; bottom:0.6in; display:flex; flex-direction:column; }

  .gold-text {
    background:linear-gradient(180deg, <?= $GOLD_LIGHT ?> 0%, <?= $GOLD_MID ?> 55%, <?= $GOLD_DEEP ?> 100%);
    -webkit-background-clip:text; background-clip:text;
    color:<?= $GOLD_MID ?>; -webkit-text-fill-color:transparent;
  }
  @supports not ((-webkit-background-clip:text) or (background-clip:text)) {
    .gold-text { color:<?= $GOLD_MID ?>; -webkit-text-fill-color:<?= $GOLD_MID ?>; }
  }

  h1.title { font-size:47px; font-weight:800; letter-spacing:0.045em; line-height:1; }
  .subtitle { font-size:18px; font-weight:600; letter-spacing:0.34em; margin-top:9px; }

  .presented { font-size:11px; letter-spacing:0.3em; color:#B9B9B9; text-transform:uppercase; margin-top:30px; }

  .recipient {
    font-family:'Brush Script MT','Segoe Script','Lucida Handwriting',cursive;
    font-size:50px; line-height:1.08; margin-top:8px; padding-bottom:6px;
  }
  .name-rule { height:1px; background:#6E6E6E; margin-top:2px; }
  .sub-line { font-size:9px; letter-spacing:0.12em; color:#9A9A9A; margin-top:9px; text-transform:uppercase; }

  .citation { font-size:10.5px; line-height:1.85; color:#A6A6A6; margin-top:14px; text-align:justify; max-width:5.6in; }

  .signatures { margin-top:auto; display:flex; gap:0.9in; }
  .signatures div { flex:1; max-width:2in; }
  .signatures .line { height:1px; background:#6E6E6E; }
  .signatures .lbl { font-size:8.5px; letter-spacing:0.22em; color:#9A9A9A; text-transform:uppercase; margin-top:7px; text-align:center; }
  .signatures .val { font-size:10.5px; color:#D2D2D2; text-align:center; margin-bottom:5px; }

  .cert-no { position:absolute; right:0.55in; bottom:0.3in; font-size:8px; letter-spacing:0.1em; color:#6A6A6A; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:11.69in; height:8.27in; }
    @page { size: A4 landscape; margin:0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <a href="<?= $cfg['url'] ?>/school/certificates">&larr; Back to certificates</a>
  <button onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

  <div class="deco">
    <div class="deco-red"></div>
    <div class="deco-gold-band"></div>
    <div class="deco-gold-edge"></div>
  </div>

  <div class="badge">
    <div class="badge-inner">
      <div class="seal-wrap">
        <div class="ribbon l"></div>
        <div class="ribbon r"></div>
        <div class="seal-outer">
          <div class="seal-ring">
            <div class="seal-core">
              <div class="top"><?= htmlspecialchars($sealTop) ?></div>
              <div class="rule"></div>
              <div class="bot"><?= htmlspecialchars($sealBottom) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($tenant['logo'])): ?>
  <div class="school-mark">
    <img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="">
    <span><?= htmlspecialchars($schoolName) ?></span>
  </div>
  <?php endif; ?>

  <div class="content">
    <h1 class="title gold-text">CERTIFICATE</h1>
    <div class="subtitle gold-text"><?= htmlspecialchars($typeLine) ?></div>

    <div class="presented">Proudly Presented To</div>
    <div class="recipient gold-text"><?= htmlspecialchars($cert['recipient_name']) ?></div>
    <div class="name-rule"></div>
    <?php if ($subLine !== ''): ?>
    <div class="sub-line"><?= htmlspecialchars($subLine) ?></div>
    <?php endif; ?>

    <p class="citation"><?= htmlspecialchars($citation) ?></p>

    <div class="signatures">
      <div>
        <div class="val"><?= $cert['issued_date'] ? date('d M Y', strtotime($cert['issued_date'])) : '&nbsp;' ?></div>
        <div class="line"></div>
        <div class="lbl">Date</div>
      </div>
      <div>
        <div class="val">&nbsp;</div>
        <div class="line"></div>
        <div class="lbl">Signature</div>
      </div>
    </div>
  </div>

  <?php if (!empty($cert['certificate_no'])): ?>
  <div class="cert-no">CERT. NO. <?= htmlspecialchars($cert['certificate_no']) ?></div>
  <?php endif; ?>

</div>
</body>
</html>

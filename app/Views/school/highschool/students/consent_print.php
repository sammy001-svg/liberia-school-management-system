<?php
// The paper consent form, on school letterhead. Blank for sending home, or filled in
// with what the family already agreed to, as a record for the child's file.
$tick = fn($on) => $on ? '&#10003;' : '&nbsp;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parent Consent Form — <?= htmlspecialchars($tenant['name'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e9ee; font-family: Arial, Helvetica, sans-serif; color: #111; line-height: 1.55; }
  .toolbar { position: sticky; top: 0; background: #1e1b2e; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; }
  .toolbar button { background: #4cae72; color: #fff; border: 0; border-radius: 6px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
  .sheet { width: 210mm; max-width: 100%; margin: 12px auto; background: #fff; padding: 14mm; }
  .head { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 14px; }
  .head img { width: 66px; height: 66px; object-fit: contain; }
  .head .school { flex: 1; text-align: center; }
  .head .name { font-size: 23px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
  .head .meta { font-size: 11px; color: #333; margin-top: 2px; }
  h1 { font-size: 16px; text-align: center; letter-spacing: .05em; margin: 0 0 4px; }
  .sub { text-align: center; font-size: 11.5px; color: #555; margin-bottom: 16px; }
  .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 22px; font-size: 13px; margin-bottom: 14px; }
  .fill { border-bottom: 1px solid #333; display: inline-block; min-width: 55%; padding: 0 4px; }
  .statement { border: 1px solid #333; padding: 12px 14px; font-size: 12.5px; margin-bottom: 14px; background: #fbfbfd; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  th, td { border: 1px solid #333; padding: 8px 10px; font-size: 12.5px; vertical-align: top; }
  th { background: #efefef; text-align: left; }
  td.box { width: 52px; text-align: center; font-size: 15px; font-weight: 700; }
  .note { font-size: 11.5px; color: #444; margin-bottom: 16px; }
  .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; margin-top: 26px; font-size: 13px; }
  .sign .line { border-bottom: 1px solid #333; height: 34px; margin-bottom: 4px; }
  .foot { margin-top: 22px; font-size: 10.5px; color: #666; border-top: 1px solid #ccc; padding-top: 8px; }
  @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; width: auto; padding: 0; } @page { size: A4; margin: 12mm; } }
</style>
</head>
<body>
<div class="toolbar">
  <span>Parent Consent Form<?= $student ? ' — ' . htmlspecialchars($student['name']) : ' (blank)' ?></span>
  <button onclick="window.print()">🖨 Print / Save as PDF</button>
</div>

<div class="sheet">
  <div class="head">
    <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
    <div class="school">
      <div class="name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
      <div class="meta"><?= htmlspecialchars((string)($tenant['address'] ?? '')) ?></div>
      <div class="meta">
        <?= !empty($tenant['phone']) ? 'Phone: ' . htmlspecialchars($tenant['phone']) : '' ?>
        <?= !empty($tenant['email']) ? ' &nbsp;·&nbsp; Email: ' . htmlspecialchars($tenant['email']) : '' ?>
      </div>
    </div>
    <?php if (!empty($tenant['logo'])): ?><span style="width:66px;"></span><?php endif; ?>
  </div>

  <h1>PARENT CONSENT — PHOTOGRAPHS AND VIDEO</h1>
  <div class="sub">Child protection · to be completed by the parent or legal guardian</div>

  <div class="fields">
    <div>Child's name: <span class="fill"><?= $student ? htmlspecialchars($student['name']) : '' ?></span></div>
    <div>Class: <span class="fill"><?= $student ? htmlspecialchars($student['class_name'] ?? '') : '' ?></span></div>
    <div>Admission no.: <span class="fill"><?= $student ? htmlspecialchars($student['admission_no']) : '' ?></span></div>
    <div>Parent / guardian: <span class="fill"><?= $consent ? htmlspecialchars($consent['parent_name']) : '' ?></span></div>
    <div>Relationship to child: <span class="fill"><?= $consent ? htmlspecialchars((string)$consent['relationship']) : '' ?></span></div>
    <div>Phone: <span class="fill"><?= $consent ? htmlspecialchars((string)$consent['parent_phone']) : '' ?></span></div>
  </div>

  <div class="statement"><?= htmlspecialchars($consent['statement_text'] ?? $statement) ?></div>

  <table>
    <thead><tr><th>Where the photographs or video may be used</th><th style="width:52px;">Yes</th><th style="width:52px;">No</th></tr></thead>
    <tbody>
      <?php foreach ($scopes as $key => [$label, $desc]): $has = $consent !== null; $on = $has && !empty($consent['allow_' . $key]); ?>
        <tr>
          <td><strong><?= htmlspecialchars($label) ?></strong><br><span style="color:#555;"><?= htmlspecialchars($desc) ?></span></td>
          <td class="box"><?= $has ? ($on ? '&#10003;' : '&nbsp;') : '&nbsp;' ?></td>
          <td class="box"><?= $has ? ($on ? '&nbsp;' : '&#10003;') : '&nbsp;' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="note">
    The school will never publish a child's full name beside a photograph without separate written permission, will not pass
    images to anyone outside the school for commercial use, and will withdraw any image on request. This permission stands
    until the parent or guardian withdraws it in writing.
  </div>

  <div class="sign">
    <div>
      <div class="line"><?= $consent ? '<span style="font-size:14px;">' . htmlspecialchars($consent['parent_name']) . '</span>' : '' ?></div>
      Signature of parent / guardian
    </div>
    <div>
      <div class="line" style="text-align:center;padding-top:8px;"><?= $consent && !empty($consent['signed_on']) ? date('j F Y', strtotime($consent['signed_on'])) : '' ?></div>
      Date
    </div>
  </div>

  <div class="sign">
    <div><div class="line"></div>Received by (school official)</div>
    <div><div class="line"></div>Date received</div>
  </div>

  <div class="foot">
    <?= htmlspecialchars($tenant['name'] ?? '') ?> · Child Protection · Form printed <?= date('j M Y') ?>
    <?php if ($consent): ?> · Recorded <?= ($consent['source'] ?? '') === 'office' ? 'from a signed paper form' : 'by the parent in the Family Portal' ?><?php endif; ?>
  </div>
</div>
</body>
</html>

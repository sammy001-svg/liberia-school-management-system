<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'Ksh'; $money = fn($n) => $cur.' '.number_format((float)$n, 2); ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Arrears &amp; Aging</div>
    <div class="page-header-sub">Outstanding balances by how long they have been owed</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/finance/accounts" class="btn btn-outline">← Student Accounts</a>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Current (0–30 days)</div>
    <div class="stat-value" style="font-size:19px;"><?= $money($totals['current']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">31–60 days</div>
    <div class="stat-value" style="font-size:19px;"><?= $money($totals['d30']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: #F97316;">
    <div class="stat-label">61–90 days</div>
    <div class="stat-value" style="font-size:19px;"><?= $money($totals['d60']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Over 90 days</div>
    <div class="stat-value" style="font-size:19px;"><?= $money($totals['d90']) ?></div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/finance/arrears" class="btn btn-outline">Reset</a>
    <div style="margin-left:auto;font-size:13px;color:var(--text-light);">
      Total outstanding: <strong><?= $money($totals['total']) ?></strong>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">Students in Arrears (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Student</th><th>Class</th>
          <th>Current</th><th>31–60</th><th>61–90</th><th>90+</th><th>Total Owing</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/students/<?= $r['student_id'] ?>" class="fw-600"><?= htmlspecialchars($r['name']) ?></a>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></div>
          </td>
          <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
          <td><?= $r['current'] > 0.009 ? $money($r['current']) : '—' ?></td>
          <td><?= $r['d30'] > 0.009 ? '<span class="badge badge-warning">'.$money($r['d30']).'</span>' : '—' ?></td>
          <td><?= $r['d60'] > 0.009 ? '<span class="badge badge-warning">'.$money($r['d60']).'</span>' : '—' ?></td>
          <td><?= $r['d90'] > 0.009 ? '<span class="badge badge-danger">'.$money($r['d90']).'</span>' : '—' ?></td>
          <td class="fw-600"><?= $money($r['total']) ?></td>
          <td><a href="<?= $cfg['url'] ?>/school/finance/accounts/<?= $r['student_id'] ?>/statement" target="_blank" class="btn btn-sm btn-outline">Statement</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">No outstanding balances. Everyone is up to date.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
      <?php if(!empty($rows)): ?>
      <tfoot>
        <tr style="font-weight:700;border-top:2px solid var(--border);">
          <td colspan="2">Totals</td>
          <td><?= $money($totals['current']) ?></td>
          <td><?= $money($totals['d30']) ?></td>
          <td><?= $money($totals['d60']) ?></td>
          <td><?= $money($totals['d90']) ?></td>
          <td><?= $money($totals['total']) ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Enter Grades</div></div>
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selectedClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load Students</button>
  </div>
</form>
<?php if (!empty($students)): ?>
<form method="POST" action="<?= $cfg['url'] ?>/school/grades/store">
  <div class="form-group" style="max-width:300px;margin-bottom:20px;">
    <label class="form-label">Exam</label>
    <select name="exam_id" class="form-control">
      <option value="">— No Exam —</option>
      <?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Enter Marks (out of 100)</div><button type="submit" class="btn btn-sm btn-primary">Save Grades</button></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Student</th><?php foreach($courses as $c): ?><th><?= htmlspecialchars($c['name']) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
          <?php foreach($courses as $c): ?>
          <td><input type="number" name="grades[<?= $s['id'] ?>][<?= $c['id'] ?>]" class="form-control" min="0" max="100" style="width:70px;padding:6px;"></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</form>
<?php else: ?><div class="card"><div class="card-body text-center text-muted">Select a class to enter grades.</div></div><?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

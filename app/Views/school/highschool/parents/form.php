<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Add Parent</div></div>
<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/parents/store">
  <div class="card"><div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Parent Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
      <div class="form-group"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Link to Student</label>
        <select name="student_id" class="form-control">
          <option value="">— Select Student —</option>
          <?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Relationship</label>
        <select name="relationship" class="form-control">
          <?php foreach(['parent','mother','father','guardian'] as $r): ?><option value="<?= $r ?>"><?= ucfirst($r) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn btn-primary">Create Parent Account</button><a href="<?= $cfg['url'] ?>/school/parents" class="btn btn-secondary">Cancel</a></div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

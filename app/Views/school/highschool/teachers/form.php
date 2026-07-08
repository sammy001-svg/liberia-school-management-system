<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title"><?= $teacher?'Edit Teacher':'Add Teacher' ?></div></div>
<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $teacher?$teacher['id'].'/update':'store' ?>">
  <div class="card"><div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($teacher['name']??'') ?>" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']??'') ?>" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Gender</label>
        <select name="gender" class="form-control">
          <option value="">— Select —</option>
          <?php foreach(['male','female','other'] as $g): ?><option value="<?= $g ?>" <?= ($teacher['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($teacher['qualification']??'') ?>" placeholder="e.g. B.Ed, MSc"></div>
      <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($teacher['specialization']??'') ?>" placeholder="e.g. Mathematics"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Assign to Class</label>
        <select name="class_id" class="form-control">
          <option value="">— Not Assigned —</option>
          <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= ($teacher['class_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Join Date</label><input type="date" name="joined_at" class="form-control" value="<?= $teacher['joined_at']??date('Y-m-d') ?>"></div>
    </div>
    <?php if(!$teacher): ?>
    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Default: Teacher@123"></div>
    <?php endif; ?>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn btn-primary"><?= $teacher?'Update':'Add' ?> Teacher</button><a href="<?= $cfg['url'] ?>/school/teachers" class="btn btn-secondary">Cancel</a></div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

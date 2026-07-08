<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title"><?= $class?'Edit Class':'Add Class' ?></div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/classes/<?= $class?$class['id'].'/update':'store' ?>">
  <div class="card"><div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Class Name *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($class['name']??'') ?>" required placeholder="e.g. Grade 7A"></div>
      <div class="form-group"><label class="form-label">Grade Level *</label><input type="text" name="grade_level" class="form-control" value="<?= htmlspecialchars($class['grade_level']??'') ?>" required placeholder="e.g. Grade 7"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Section</label><input type="text" name="section" class="form-control" value="<?= htmlspecialchars($class['section']??'') ?>" placeholder="e.g. A"></div>
      <div class="form-group"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="<?= $class['capacity']??40 ?>"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Class Teacher</label>
      <select name="teacher_id" class="form-control">
        <option value="">— Not Assigned —</option>
        <?php foreach($teachers as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($class['class_teacher_id']??'')==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $class?'Update':'Create' ?> Class</button>
    <a href="<?= $cfg['url'] ?>/school/classes" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

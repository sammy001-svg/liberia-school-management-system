<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title"><?= $isPin ? 'Change PIN' : 'Change Password' ?></div>
    <div class="page-header-sub"><?= $isPin ? 'Update the PIN you use to sign in' : 'Update the password you use to sign in' ?></div>
  </div>
</div>

<div style="max-width:480px;">
  <div class="card">
    <div class="card-body">
      <form method="POST" action="<?= $cfg['url'] ?>/account/change-password">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group">
          <label class="form-label">Current <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="current_secret" class="form-control" required autofocus
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php endif; ?>>
        </div>
        <div class="form-group">
          <label class="form-label">New <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="new_secret" class="form-control" required
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php else: ?>minlength="8"<?php endif; ?>>
          <div class="form-hint"><?= $isPin ? 'Exactly 4 digits.' : 'At least 8 characters.' ?></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="confirm_secret" class="form-control" required
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php endif; ?>>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Save <?= $isPin ? 'PIN' : 'Password' ?></button>
      </form>
    </div>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

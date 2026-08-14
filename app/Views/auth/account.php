<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
  // What this account signs in with decides which field matters, so the copy says so
  // instead of leaving someone to guess whether clearing a field will lock them out.
  $usernameMatters = $accountType === 'parent' && $loginModes['parent'] === 'username_password';
  $emailOptional   = $accountType === 'student' && $loginModes['student'] === 'admission_pin';
?>
<div class="page-header">
  <div>
    <div class="page-header-title">My Account</div>
    <div class="page-header-sub">Change the email or username you sign in with, and your <?= $isPin ? 'PIN' : 'password' ?></div>
  </div>
</div>

<div style="max-width:520px;">

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <div class="card-title">Sign-In Details</div>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= $cfg['url'] ?>/account/login-details">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group">
          <label class="form-label">Login Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($me['email'] ?? '') ?>"
                 placeholder="name@school.com" autocomplete="email">
          <div class="form-hint">
            <?= $emailOptional
                ? 'You sign in with your admission number, so this is only for school records.'
                : 'This is what you type in the Email or Username box when you sign in.' ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Username<?= $usernameMatters ? '' : ' (optional)' ?></label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($me['username'] ?? '') ?>"
                 placeholder="e.g. j.doe" autocomplete="username">
          <div class="form-hint">
            <?= $usernameMatters
                ? 'You sign in with this username — it cannot be left blank.'
                : 'An alternative to your email. Letters, numbers, dot, dash or underscore.' ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Current <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="current_secret" class="form-control" required autocomplete="current-password"
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php endif; ?>>
          <div class="form-hint">Confirms it's really you before your sign-in details change.</div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Save Sign-In Details</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Change <?= $isPin ? 'PIN' : 'Password' ?></div>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= $cfg['url'] ?>/account/change-password">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group">
          <label class="form-label">Current <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="current_secret" class="form-control" required autocomplete="current-password"
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php endif; ?>>
        </div>
        <div class="form-group">
          <label class="form-label">New <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="new_secret" class="form-control" required autocomplete="new-password"
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php else: ?>minlength="8"<?php endif; ?>>
          <div class="form-hint"><?= $isPin ? 'Exactly 4 digits.' : 'At least 8 characters.' ?> Use the eye button to check what you typed.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New <?= $isPin ? 'PIN' : 'Password' ?></label>
          <input type="password" name="confirm_secret" class="form-control" required autocomplete="new-password"
                 <?php if($isPin): ?>inputmode="numeric" pattern="[0-9]*" maxlength="4"<?php endif; ?>>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Save <?= $isPin ? 'PIN' : 'Password' ?></button>
      </form>
    </div>
  </div>

  <?php if (!empty($canManageRoles)): ?>
  <div class="form-hint" style="margin-top:14px;">
    To change someone else's login email or password, use
    <a href="<?= $cfg['url'] ?>/school/users">Login Accounts</a>.
  </div>
  <?php endif; ?>

</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

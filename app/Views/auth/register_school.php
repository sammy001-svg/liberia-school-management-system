<?php
$cfg      = require ROOT_DIR . '/config/app.php';
$appName  = $cfg['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register your School — <?= htmlspecialchars($appName) ?></title>
<link rel="stylesheet" href="<?= $cfg['url'] ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="max-width:500px;">
    <div class="login-logo">
      <div style="width:56px;height:56px;background:linear-gradient(135deg,#10B981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:26px;font-weight:900;color:#fff;">S</div>
      <h1>Register School</h1>
      <p>Get started with your institution portal</p>
    </div>

    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <form action="<?= $cfg['url'] ?>/register/school" method="POST">
      <div class="form-group">
        <label class="form-label">School Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Green Valley High" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Institution Type *</label>
        <select name="institution_type" class="form-control" required>
          <option value="high_school">High School</option>
          <option value="university">University</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Admin Email *</label>
        <input type="email" name="email" class="form-control" placeholder="admin@school.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Set Admin Password *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label class="form-label">Subscription Plan *</label>
        <select name="plan_id" class="form-control" required>
          <?php foreach($plans as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> — $<?= $p['price_monthly'] ?>/mo</option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
        Register Now
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:14px;">
      Already have an account? <a href="<?= $cfg['url'] ?>/login" style="color:var(--primary);font-weight:600;">Sign In</a>
    </p>
  </div>
</div>
</body>
</html>

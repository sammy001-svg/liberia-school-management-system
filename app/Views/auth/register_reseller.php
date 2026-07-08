<?php
$cfg      = require ROOT_DIR . '/config/app.php';
$appName  = $cfg['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Become a Reseller — <?= htmlspecialchars($appName) ?></title>
<link rel="stylesheet" href="<?= $cfg['url'] ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="max-width:500px;">
    <div class="login-logo">
      <div style="width:56px;height:56px;background:linear-gradient(135deg,#10B981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:26px;font-weight:900;color:#fff;">R</div>
      <h1>Reseller Application</h1>
      <p>Partner with us and start earning</p>
    </div>

    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <form action="<?= $cfg['url'] ?>/register/reseller" method="POST">
      <div class="form-group">
        <label class="form-label">Company Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Acme Tech Solutions" required>
      </div>

      <div class="form-group">
        <label class="form-label">Business Email *</label>
        <input type="email" name="email" class="form-control" placeholder="partners@company.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Set Password *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label class="form-label">Select Package *</label>
        <select name="reseller_plan_id" class="form-control" required>
          <option value="">-- Choose a Reseller Package --</option>
          <?php foreach($plans as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= htmlspecialchars($p['name']) ?> - Ksh <?= number_format($p['price'], 2) ?> (<?= $p['max_schools'] ?> Schools)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="background:#f9fafb;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;color:#666;">
        By submitting this form, you apply to become an authorized reseller. Your account will be set to <strong>pending</strong> status until reviewed by our administration team.
      </div>

      <button type="submit" class="btn btn-secondary btn-block btn-lg" style="margin-top:8px;">
        Submit Application
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:14px;">
      Already have an account? <a href="<?= $cfg['url'] ?>/login" style="color:var(--primary);font-weight:600;">Sign In</a>
    </p>
  </div>
</div>
</body>
</html>

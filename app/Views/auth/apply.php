<?php
$cfg      = require ROOT_DIR . '/config/app.php';
$branding = $branding ?? ($_SESSION['branding'] ?? null);
$appName  = $branding['name'] ?? $cfg['name'];
$appLogo  = $branding['logo'] ?? null;
$primaryColor   = $branding['primary_color']   ?? null;
$secondaryColor = $branding['secondary_color'] ?? null;
$faviconSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='" . ($primaryColor ?: '#10B981') . "'/><text x='50' y='68' font-family='Arial,sans-serif' font-size='58' font-weight='900' fill='white' text-anchor='middle'>" . strtoupper(substr($appName, 0, 1)) . "</text></svg>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Online Application') ?> — <?= htmlspecialchars($appName) ?></title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($faviconSvg) ?>">
<script>(function(){try{if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');}catch(e){}})();</script>
<link rel="stylesheet" href="<?= $cfg['url'] ?>/assets/css/style.css">
<?php if ($primaryColor): ?>
<style>
  :root {
    --primary: <?= htmlspecialchars($primaryColor) ?>;
    --secondary: <?= htmlspecialchars($secondaryColor ?? '#059669') ?>;
  }
</style>
<?php endif; ?>
</head>
<body>
<div class="login-split">
  <div class="login-form-panel">
    <div class="login-box" style="max-width:640px;">
      <div class="login-logo">
        <?php if ($appLogo): ?>
          <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo">
        <?php else: ?>
          <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:26px;font-weight:900;color:#fff;"><?= strtoupper(substr($appName,0,1)) ?></div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($appName) ?></h1>
        <p>Online Admission Application</p>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>">
          <?= htmlspecialchars($flash['message']) ?>
        </div>
      <?php endif; ?>

      <form action="<?= $cfg['url'] ?>/apply/submit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="modal-section-title">Student Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
              <option value="">— Select —</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Desired Class</label>
            <select name="desired_class_id" class="form-control">
              <option value="">— Not Sure —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-section-title">Parent / Guardian Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Guardian Name *</label>
            <input type="text" name="guardian_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Relationship</label>
            <input type="text" name="guardian_relationship" class="form-control" placeholder="e.g. Mother, Father">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="guardian_phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="guardian_email" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <input type="text" name="address" class="form-control">
        </div>

        <div class="modal-section-title">Previous School (if any)</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Previous School Name</label>
            <input type="text" name="previous_school" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Previous Class</label>
            <input type="text" name="previous_class" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Additional Notes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Anything else the school should know"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">Submit Application</button>
      </form>

      <p style="text-align:center;margin-top:24px;font-size:13px;">
        <a href="<?= $cfg['url'] ?>/login">&larr; Back to Sign In</a>
      </p>
      <p style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
        Powered by <?= htmlspecialchars($appName) ?> &copy; <?= date('Y') ?>
      </p>
    </div>
  </div>
</div>
</body>
</html>

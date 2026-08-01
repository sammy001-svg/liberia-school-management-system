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

      <form action="<?= $cfg['url'] ?>/apply/submit" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="modal-section-title">Photographs</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Student's Photo</label>
            <input type="file" name="student_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          </div>
          <div class="form-group">
            <label class="form-label">Parent's Photo</label>
            <input type="file" name="parent_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div class="modal-section-title">Personal Profile</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Student's Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Student's Home Address</label>
          <input type="text" name="address" class="form-control">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Mother's Name</label>
            <input type="text" name="mother_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="mother_phone" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Father's Name</label>
            <input type="text" name="father_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="father_phone" class="form-control">
          </div>
        </div>
        <div class="form-hint" style="margin:-8px 0 4px;">Please provide at least one parent's name and phone number.</div>

        <div class="modal-section-title">Previous School Information</div>
        <div class="form-group">
          <label class="form-label">Last School Attended</label>
          <input type="text" name="previous_school" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Last School Address</label>
          <input type="text" name="previous_school_address" class="form-control">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name of Principal</label>
            <input type="text" name="principal_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="principal_phone" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name of Sponsor</label>
            <input type="text" name="sponsor_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="sponsor_phone" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Last Class</label>
            <input type="text" name="last_class" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Class Promoted To</label>
            <select name="desired_class_id" class="form-control">
              <option value="">— Select a class —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-section-title">Emergency Contact</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name</label>
            <input type="text" name="emergency_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="emergency_phone" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Relationship</label>
            <input type="text" name="emergency_relationship" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="emergency_email" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <input type="text" name="emergency_address" class="form-control">
        </div>

        <div class="modal-section-title">Medical Information</div>
        <div class="form-group">
          <label class="form-label">Child's medical condition, if any</label>
          <textarea name="medical_conditions" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Food / medicine allergies</label>
          <textarea name="allergies" class="form-control" rows="2"></textarea>
        </div>

        <div class="modal-section-title">Persons Authorized to Pick Up the Child</div>
        <?php for($i = 0; $i < 3; $i++): ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= $i + 1 ?>. Name</label>
            <input type="text" name="pickup_name[]" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="pickup_phone[]" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="pickup_address[]" class="form-control">
        </div>
        <?php endfor; ?>

        <div class="modal-section-title">Attached Documents</div>
        <p style="font-size:12.5px;color:var(--text-muted);margin:-4px 0 14px;">
          Attach what you have now — the school can request anything missing later.
          PDF, JPG, PNG or WEBP, up to 5MB each.
        </p>
        <?php $n = 0; foreach($documentSlots as $slot => $label): $n++; ?>
        <div class="form-group">
          <label class="form-label"><?= $n ?>. <?= htmlspecialchars($label) ?></label>
          <input type="file" name="doc_<?= htmlspecialchars($slot) ?>" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
        </div>
        <?php endforeach; ?>

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

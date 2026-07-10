<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">School Settings</div>
    <div class="page-header-sub">General information, localization and branding</div>
  </div>
</div>

<div class="card profile-hero">
  <div class="profile-hero-body">
    <?php if(!empty($tenant['logo'])): ?>
      <div class="avatar avatar-xl avatar-sq" style="padding:0;overflow:hidden;background:#fff;"><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
    <?php else: ?>
      <div class="avatar avatar-xl avatar-sq"><?= strtoupper(substr($tenant['name'] ?? '?',0,1)) ?></div>
    <?php endif; ?>
    <div class="profile-hero-info">
      <div class="profile-hero-name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
      <div class="profile-hero-meta">
        <span class="meta-chip">🏷️ <?= htmlspecialchars($tenant['slug'] ?? '—') ?></span>
        <span class="meta-chip"><?= $tenant['institution_type']==='university'?'🎓 University':'🏫 High School' ?></span>
        <span class="badge badge-<?= $tenant['status']==='active'?'success':($tenant['status']==='trial'?'info':($tenant['status']==='suspended'?'danger':'warning')) ?>"><?= ucfirst($tenant['status'] ?? 'active') ?></span>
        <?php if($tenant['status']==='trial' && !empty($tenant['trial_ends_at'])): ?>
          <span class="meta-chip">⏳ Trial ends <?= date('d M Y', strtotime($tenant['trial_ends_at'])) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/settings/update" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
  <div class="card-header"><div class="card-title">🏫 General Information</div></div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">School Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tenant['name']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tenant['email']??'') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($tenant['phone']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="<?= htmlspecialchars($tenant['country']??'') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control"><?= htmlspecialchars($tenant['address']??'') ?></textarea></div>
  </div></div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">🌍 Localization</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Timezone</label>
          <select name="timezone" class="form-control">
            <?php foreach(['UTC','Africa/Nairobi','Africa/Lagos','Africa/Cairo','Europe/London','America/New_York','Asia/Dubai'] as $tz): ?>
              <option value="<?= $tz ?>" <?= ($tenant['timezone']??'UTC')===$tz?'selected':'' ?>><?= $tz ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" placeholder="2024/2025" value="<?= htmlspecialchars($tenant['academic_year']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Currency Symbol</label><input type="text" name="currency" class="form-control" placeholder="e.g. Ksh, $, UGX" value="<?= htmlspecialchars($tenant['currency']??'Ksh') ?>"></div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">🖼️ School Logo</div></div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Logo</label>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <div id="logoPreviewBox" style="width:72px;height:72px;border-radius:var(--radius-sm);border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
            <?php if(!empty($tenant['logo'])): ?>
              <img id="logoPreviewImg" src="<?= htmlspecialchars($tenant['logo']) ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
            <?php else: ?>
              <span id="logoPreviewImg" style="color:var(--text-muted);font-size:11px;">No logo</span>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:220px;">
            <input type="file" name="logo" id="logoInput" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/*">
            <div class="form-hint">JPG, PNG, WEBP, GIF or SVG — up to 2MB. Appears in the sidebar, login page, ID cards, report cards, payslips and invoices.</div>
            <?php if(!empty($tenant['logo'])): ?>
              <label style="display:flex;align-items:center;gap:6px;margin-top:8px;cursor:pointer;font-size:12.5px;color:var(--text-muted);">
                <input type="checkbox" name="remove_logo" value="1" onchange="document.getElementById('logoInput').disabled=this.checked;">
                Remove current logo
              </label>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">🎨 White-Labeling &amp; Domain</div></div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Custom Domain</label>
        <input type="text" name="domain" class="form-control" placeholder="portal.your-school.com" value="<?= htmlspecialchars($tenant['domain']??'') ?>">
        <div class="form-hint">Point your CNAME record to our system IP.</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Primary Color</label>
          <input type="color" name="primary_color" id="primaryColorInput" class="form-control" style="height:42px;padding:4px;" value="<?= $tenant['primary_color']??'#10B981' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Secondary Color</label>
          <input type="color" name="secondary_color" id="secondaryColorInput" class="form-control" style="height:42px;padding:4px;" value="<?= $tenant['secondary_color']??'#059669' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Accent Color</label>
          <input type="color" name="accent_color" id="accentColorInput" class="form-control" style="height:42px;padding:4px;" value="<?= $tenant['accent_color']??'#34D399' ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Preview</label>
        <div style="display:flex;gap:8px;">
          <div id="previewPrimary" style="flex:1;height:44px;border-radius:var(--radius-sm);background:<?= $tenant['primary_color']??'#10B981' ?>;"></div>
          <div id="previewSecondary" style="flex:1;height:44px;border-radius:var(--radius-sm);background:<?= $tenant['secondary_color']??'#059669' ?>;"></div>
          <div id="previewAccent" style="flex:1;height:44px;border-radius:var(--radius-sm);background:<?= $tenant['accent_color']??'#34D399' ?>;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">🔐 Login &amp; Security</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Student Login Method</label>
          <select name="student_login_mode" class="form-control">
            <option value="admission_pin" <?= ($tenant['student_login_mode']??'admission_pin')==='admission_pin'?'selected':'' ?>>Admission No. + PIN</option>
            <option value="email_password" <?= ($tenant['student_login_mode']??'')==='email_password'?'selected':'' ?>>Email + Password</option>
          </select>
          <div class="form-hint">How students sign in on the login page's Student tab.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Parent Login Method</label>
          <select name="parent_login_mode" class="form-control">
            <option value="username_password" <?= ($tenant['parent_login_mode']??'username_password')==='username_password'?'selected':'' ?>>Username + Password</option>
            <option value="email_password" <?= ($tenant['parent_login_mode']??'')==='email_password'?'selected':'' ?>>Email + Password</option>
          </select>
          <div class="form-hint">How parents sign in on the login page's Parent tab.</div>
        </div>
      </div>
    </div>
  </div>
  <div style="margin-top:20px;"><button type="submit" class="btn btn-primary">Save Settings</button></div>
</form>
</div>

<script>
document.getElementById('primaryColorInput').addEventListener('input', function(){ document.getElementById('previewPrimary').style.background = this.value; });
document.getElementById('secondaryColorInput').addEventListener('input', function(){ document.getElementById('previewSecondary').style.background = this.value; });
document.getElementById('accentColorInput').addEventListener('input', function(){ document.getElementById('previewAccent').style.background = this.value; });

document.getElementById('logoInput').addEventListener('change', function(){
  if (!this.files || !this.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e){
    const box = document.getElementById('logoPreviewBox');
    box.innerHTML = '<img id="logoPreviewImg" src="' + e.target.result + '" alt="Logo" style="width:100%;height:100%;object-fit:contain;">';
  };
  reader.readAsDataURL(this.files[0]);
});
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

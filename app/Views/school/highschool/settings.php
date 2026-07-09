<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">School Settings</div>
    <div class="page-header-sub">General information, localization and branding</div>
  </div>
</div>

<div class="card profile-hero">
  <div class="profile-hero-body">
    <div class="avatar avatar-xl avatar-sq"><?= strtoupper(substr($tenant['name'] ?? '?',0,1)) ?></div>
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
<form method="POST" action="<?= $cfg['url'] ?>/school/settings/update">
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
  <div style="margin-top:20px;"><button type="submit" class="btn btn-primary">Save Settings</button></div>
</form>
</div>

<script>
document.getElementById('primaryColorInput').addEventListener('input', function(){ document.getElementById('previewPrimary').style.background = this.value; });
document.getElementById('secondaryColorInput').addEventListener('input', function(){ document.getElementById('previewSecondary').style.background = this.value; });
document.getElementById('accentColorInput').addEventListener('input', function(){ document.getElementById('previewAccent').style.background = this.value; });
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

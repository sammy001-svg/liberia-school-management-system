<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">White-Label Branding</div></div>
<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/reseller/branding/update" enctype="multipart/form-data">
  <div class="card">
    <div class="card-header"><div class="card-title">Brand Identity</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Platform / Brand Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($reseller['name']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Currency Symbol</label>
          <input type="text" name="currency" class="form-control" placeholder="e.g. Ksh, $, UGX" value="<?= htmlspecialchars($reseller['currency']??'Ksh') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Primary Color</label>
          <input type="color" name="primary_color" class="form-control" style="height:42px;padding:4px" value="<?= $reseller['primary_color']??'#10B981' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Secondary Color</label>
          <input type="color" name="secondary_color" class="form-control" style="height:42px;padding:4px" value="<?= $reseller['secondary_color']??'#059669' ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Custom Domain (CNAME)</label>
        <input type="text" name="domain" class="form-control" placeholder="app.yourcompany.com" value="<?= htmlspecialchars($reseller['domain']??'') ?>">
        <div class="form-hint">Point your domain CNAME to this platform's server IP, then set it here.</div>
      </div>
    </div>
  </div>
  <div style="margin-top:20px;">
    <button type="submit" class="btn btn-primary">Save Branding</button>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

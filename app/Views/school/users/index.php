<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Login Accounts</div>
    <div class="page-header-sub">Correct the email or username an account signs in with, and set its password or PIN</div>
  </div>
</div>

<div class="alert alert-info" style="margin-bottom:20px;">
  Existing passwords are stored encrypted and cannot be read back by anyone, including you.
  To give someone a password you know, set a new one here — their old one stops working immediately.
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Accounts</div>
    <div class="stat-value"><?= (int)($counts['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">No Email On File</div>
    <div class="stat-value"><?= (int)($counts['no_email'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Never Signed In</div>
    <div class="stat-value"><?= (int)($counts['never_signed_in'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, username or admission no…" class="form-control" style="max-width:320px;">
    <select name="type" class="form-control" style="max-width:200px;">
      <option value="">All Account Types</option>
      <?php foreach (['staff'=>'Staff & Admin','teacher'=>'Teachers','student'=>'Students','parent'=>'Parents'] as $key => $lbl): ?>
        <option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>><?= $lbl ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/users" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">Accounts (<?= (int)$total ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Name</th><th>Type / Role</th><th>Login Email</th><th>Username</th><th>Last Sign-In</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <?php
          // A student signs in with a PIN only where the school is configured that way —
          // the form has to ask for exactly what that account's login expects.
          $isPin = $u['account_type'] === 'student' && $loginModes['student'] === 'admission_pin';
          $typeLabels = ['staff'=>'Staff','teacher'=>'Teacher','student'=>'Student','parent'=>'Parent'];
          $payload = json_encode([
            'id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'username' => $u['username'],
            'is_pin' => $isPin, 'type' => $typeLabels[$u['account_type']] ?? 'Staff',
          ], JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($u['name']) ?></div>
                <?php if(!empty($u['admission_no'])): ?>
                  <div style="font-size:11px;color:var(--text-muted);font-family:monospace;"><?= htmlspecialchars($u['admission_no']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-info"><?= htmlspecialchars($typeLabels[$u['account_type']] ?? 'Staff') ?></span>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars($u['role_name']) ?></div>
          </td>
          <td><?= $u['email'] ? htmlspecialchars($u['email']) : '<span style="color:var(--text-muted)">—</span>' ?></td>
          <td style="font-family:monospace;font-size:12px;"><?= $u['username'] ? htmlspecialchars($u['username']) : '<span style="color:var(--text-muted);font-family:inherit;">—</span>' ?></td>
          <td style="font-size:12px;color:var(--text-muted);">
            <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openLoginDetailsModal(<?= $payload ?>)'>✉ Email / Username</button>
              <button type="button" class="btn btn-sm btn-outline" onclick='openSetPasswordModal(<?= $payload ?>)'>🔑 Set <?= $isPin ? 'PIN' : 'Password' ?></button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/users/<?= $u['id'] ?>/generate-password"
                    data-confirm="Generate a new <?= $isPin ? 'PIN' : 'password' ?> for <?= htmlspecialchars($u['name']) ?>? It is shown once and their current one stops working."
                    data-confirm-title="Generate <?= $isPin ? 'PIN' : 'Password' ?>" data-confirm-label="Generate">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-outline">🎲 Generate</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($users)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">🔐</div>
            <div class="empty-state-text">No accounts match this search.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Email / Username Modal -->
<div class="modal-overlay" id="loginDetailsModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="loginDetailsTitle">Login Details</div>
      <button class="modal-close" onclick="document.getElementById('loginDetailsModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="loginDetailsForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Login Email</label>
          <input type="email" name="email" id="loginDetailsEmail" class="form-control" placeholder="name@school.com">
          <div class="form-hint">Used to sign in on the Staff &amp; Admin tab. Leave blank to remove it.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" id="loginDetailsUsername" class="form-control" placeholder="e.g. j.doe">
          <div class="form-hint" id="loginDetailsUsernameHint">An alternative to the email — letters, numbers, dot, dash or underscore.</div>
        </div>
        <div class="alert alert-warning" id="loginDetailsWarning" style="display:none;font-size:12.5px;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('loginDetailsModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Login Details</button>
      </div>
    </form>
  </div>
</div>

<!-- Set Password Modal -->
<div class="modal-overlay" id="setPasswordModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title" id="setPasswordTitle">Set Password</div>
      <button class="modal-close" onclick="document.getElementById('setPasswordModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="setPasswordForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" id="setPasswordLabel">New Password</label>
          <input type="password" name="new_password" id="setPasswordNew" class="form-control" required>
          <div class="form-hint" id="setPasswordHint">At least 8 characters. Use the eye button to check what you typed.</div>
        </div>
        <div class="form-group">
          <label class="form-label" id="setPasswordConfirmLabel">Confirm New Password</label>
          <input type="password" name="confirm_password" id="setPasswordConfirm" class="form-control" required>
        </div>
        <div class="alert alert-warning" style="font-size:12.5px;">
          The user's current secret stops working as soon as you save. Tell them the new one.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('setPasswordModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
var USERS_BASE = '<?= $cfg['url'] ?>/school/users/';

function openLoginDetailsModal(u) {
  document.getElementById('loginDetailsForm').action = USERS_BASE + u.id + '/credentials';
  document.getElementById('loginDetailsTitle').textContent = 'Login Details — ' + u.name;
  document.getElementById('loginDetailsEmail').value = u.email || '';
  document.getElementById('loginDetailsUsername').value = u.username || '';

  // What each account type actually signs in with differs, so say so rather than
  // letting an admin clear the one identifier that account depends on.
  var warn = document.getElementById('loginDetailsWarning');
  var notes = {
    'Student': 'Students sign in with their admission number, so these fields are optional for them.',
    'Parent':  'Parents sign in with their username where the school is set up that way — clearing it would lock them out.',
    'Teacher': 'Teachers may sign in with either field. At least one must be filled in.',
    'Staff':   'Staff may sign in with either field. At least one must be filled in.'
  };
  warn.textContent = notes[u.type] || '';
  warn.style.display = warn.textContent ? 'block' : 'none';

  document.getElementById('loginDetailsModal').classList.add('open');
}

function openSetPasswordModal(u) {
  var form = document.getElementById('setPasswordForm');
  form.reset();
  form.action = USERS_BASE + u.id + '/password';

  var word = u.is_pin ? 'PIN' : 'Password';
  document.getElementById('setPasswordTitle').textContent = 'Set ' + word + ' — ' + u.name;
  document.getElementById('setPasswordLabel').textContent = 'New ' + word;
  document.getElementById('setPasswordConfirmLabel').textContent = 'Confirm New ' + word;
  document.getElementById('setPasswordHint').textContent = u.is_pin
    ? 'Exactly 4 digits. Use the eye button to check what you typed.'
    : 'At least 8 characters. Use the eye button to check what you typed.';

  [document.getElementById('setPasswordNew'), document.getElementById('setPasswordConfirm')].forEach(function(input){
    if (u.is_pin) {
      input.setAttribute('inputmode', 'numeric');
      input.setAttribute('pattern', '[0-9]*');
      input.setAttribute('maxlength', '4');
      input.removeAttribute('minlength');
    } else {
      input.removeAttribute('inputmode');
      input.removeAttribute('pattern');
      input.removeAttribute('maxlength');
      input.setAttribute('minlength', '8');
    }
  });

  document.getElementById('setPasswordModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

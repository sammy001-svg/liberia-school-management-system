<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Platform Overview</div>
    <div class="page-header-sub">Welcome back, <?= htmlspecialchars($user['name'] ?? 'Admin') ?> — Super Admin Dashboard</div>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--primary)">
    <div class="stat-icon" style="background:rgba(79,70,229,0.15);color:var(--primary)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
    </div>
    <div class="stat-value"><?= number_format($stats['resellers']) ?></div>
    <div class="stat-label">Total Resellers</div>
  </div>

  <div class="stat-card" style="--card-color:var(--accent)">
    <div class="stat-icon" style="background:rgba(6,182,212,0.15);color:var(--accent)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
    </div>
    <div class="stat-value"><?= number_format($stats['schools']) ?></div>
    <div class="stat-label">Schools / Institutions</div>
  </div>

  <div class="stat-card" style="--card-color:var(--success)">
    <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:var(--success)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
    </div>
    <div class="stat-value"><?= number_format($stats['students']) ?></div>
    <div class="stat-label">Total Students</div>
  </div>

  <div class="stat-card" style="--card-color:var(--warning)">
    <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:var(--warning)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>
    <div class="stat-value"><?= number_format($stats['teachers']) ?></div>
    <div class="stat-label">Total Teachers</div>
  </div>

  <div class="stat-card" style="--card-color:#10B981">
    <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#10B981">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
    </div>
    <div class="stat-value">Ksh <?= number_format($stats['revenue'], 2) ?></div>
    <div class="stat-label">Total Revenue</div>
  </div>

  <div class="stat-card" style="--card-color:var(--secondary)">
    <div class="stat-icon" style="background:rgba(124,58,237,0.15);color:var(--secondary)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="stat-value"><?= number_format($stats['active_subs']) ?></div>
    <div class="stat-label">Active Subscriptions</div>
  </div>
</div>

<!-- TABLES ROW -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <!-- Recent Schools -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Schools</div>
      <a href="<?= $cfg['url'] ?>/admin/schools" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>School</th><th>Type</th><th>Reseller</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentSchools as $s): ?>
          <tr>
            <td><span class="fw-600"><?= htmlspecialchars($s['name']) ?></span></td>
            <td><span class="badge badge-info"><?= str_replace('_',' ', ucfirst($s['institution_type'])) ?></span></td>
            <td><?= htmlspecialchars($s['reseller_name'] ?? '—') ?></td>
            <td>
              <span class="badge badge-<?= $s['status'] === 'active' ? 'success' : ($s['status'] === 'suspended' ? 'danger' : 'warning') ?>">
                <?= ucfirst($s['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentSchools)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:32px;">No schools yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Resellers -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Reseller Network</div>
      <a href="<?= $cfg['url'] ?>/admin/resellers" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Reseller</th><th>Schools</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentResellers as $r): ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars($r['name']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></div>
            </td>
            <td><?= $r['school_count'] ?></td>
            <td>
              <span class="badge badge-<?= $r['status'] === 'active' ? 'success' : ($r['status'] === 'suspended' ? 'danger' : 'warning') ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentResellers)): ?>
            <tr><td colspan="3" class="text-center text-muted" style="padding:32px;">No resellers yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card mt-24">
  <div class="card-header"><div class="card-title">Quick Actions</div></div>
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] ?>/admin/resellers/create" class="btn btn-primary">+ Add Reseller</a>
    <a href="<?= $cfg['url'] ?>/admin/schools/create" class="btn btn-secondary">+ Add School</a>
    <a href="<?= $cfg['url'] ?>/admin/plans/create" class="btn btn-secondary">+ Create Plan</a>
    <a href="<?= $cfg['url'] ?>/admin/users" class="btn btn-secondary">Manage Users</a>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

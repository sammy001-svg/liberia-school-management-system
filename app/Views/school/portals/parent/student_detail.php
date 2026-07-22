<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title"><?= htmlspecialchars($student['name']) ?>'s Profile</div>
    <a href="<?= $cfg['url'] ?>/parent/dashboard" class="btn btn-secondary">Back to My Kids</a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Attendance</div>
        <div class="stat-value">
            <?php 
                $rate = $attendance['total'] > 0 ? round(($attendance['present'] / $attendance['total']) * 100) : 0;
                echo $rate . '%';
            ?>
        </div>
        <div class="stat-sub"><?= $attendance['present'] ?> / <?= $attendance['total'] ?> days recorded</div>
    </div>
    <div class="stat-card" style="--card-color: var(--danger);">
        <div class="stat-label">Fee Balance</div>
        <div class="stat-value">
            <?php 
                $balance = 0;
                foreach($invoices as $inv) if($inv['status'] !== 'paid') $balance += $inv['amount'];
                echo '$' . number_format($balance, 2);
            ?>
        </div>
        <div class="stat-sub">Across all terms</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr; gap:24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">🚌 Transport</div></div>
        <div class="card-body">
            <?php if($busInfo): ?>
            <div class="detail-list">
                <div class="detail-item">
                    <div class="detail-icon">🛣️</div>
                    <div><div class="detail-label">Route</div><div class="detail-value"><?= htmlspecialchars($busInfo['route_name']) ?></div></div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">📍</div>
                    <div><div class="detail-label">Pickup Stop</div><div class="detail-value"><?= htmlspecialchars($busInfo['pickup_stop'] ?: 'Not specified') ?></div></div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">⏰</div>
                    <div><div class="detail-label">Departure / Return</div><div class="detail-value"><?= $busInfo['departure_time'] ? date('g:i A', strtotime($busInfo['departure_time'])) : '—' ?> / <?= $busInfo['return_time'] ? date('g:i A', strtotime($busInfo['return_time'])) : '—' ?></div></div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">🚐</div>
                    <div><div class="detail-label">Bus</div><div class="detail-value"><?= htmlspecialchars($busInfo['bus_number'] ?? 'Not assigned') ?><?= $busInfo['plate_number'] ? ' ('.htmlspecialchars($busInfo['plate_number']).')' : '' ?></div></div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">🧑‍✈️</div>
                    <div><div class="detail-label">Driver</div><div class="detail-value"><?= htmlspecialchars($busInfo['driver_name'] ?? 'Not assigned') ?><?= $busInfo['driver_phone'] ? ' — '.htmlspecialchars($busInfo['driver_phone']) : '' ?></div></div>
                </div>
                <?php if($busInfo['stops']): ?>
                <div class="detail-item">
                    <div class="detail-icon">🗺️</div>
                    <div><div class="detail-label">All Stops</div><div class="detail-value"><?= htmlspecialchars($busInfo['stops']) ?></div></div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🚌</div>
                <div class="empty-state-text">Not currently assigned to a bus route.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
          <div class="card-title">Academic Results</div>
          <a href="<?= $cfg['url'] ?>/parent/student/<?= $student['id'] ?>/report-card" target="_blank" class="btn btn-sm btn-primary">📄 View Full Grade Sheet</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam</th>
                        <th>Marks</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($grades as $g): ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($g['course_name']) ?></td>
                        <td><?= htmlspecialchars($g['exam_name']) ?></td>
                        <td><?= $g['marks_obtained'] ?> / <?= $g['total_marks'] ?></td>
                        <td><span class="badge badge-primary"><?= $g['grade_letter'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($grades)): ?>
                    <tr><td colspan="4">
                        <div class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <div class="empty-state-text">No published results yet. Results appear here once the school finalizes and publishes them.</div>
                        </div>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Recent Invoices</div></div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($invoices as $i): ?>
                    <tr>
                        <td>#INV-<?= $i['id'] ?></td>
                        <td><?= htmlspecialchars($i['description']) ?></td>
                        <td class="fw-700"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($i['amount'], 2) ?></td>
                        <td>
                            <?php 
                            $badge = $i['status'] === 'paid' ? 'badge-success' : ($i['status'] === 'partial' ? 'badge-warning' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badge ?>"><?= strtoupper($i['status']) ?></span>
                        </td>
                        <td><?= date('M d, Y', strtotime($i['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

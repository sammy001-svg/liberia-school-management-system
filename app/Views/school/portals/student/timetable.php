<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">My Academic Schedule</div>
    <div class="page-header-sub">Your lessons by period, with breaks and lunch.</div>
  </div>
</div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Period</th>
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php $currentDay = ''; foreach($timetable as $t): ?>
                <?php
                    $day = $t['day_of_week'] ?? '';
                    $type = $t['entry_type'] ?? 'lesson';
                    $isBreak = $type !== 'lesson';
                ?>
                <tr<?= $isBreak ? ' style="background:rgba(245,158,11,0.08);"' : '' ?>>
                    <td class="fw-700">
                        <?php if($day !== $currentDay): $currentDay = $day; echo htmlspecialchars(ucfirst($day)); endif; ?>
                    </td>
                    <td>
                        <?php if($isBreak): ?>
                            <span class="badge badge-warning"><?= htmlspecialchars($t['label'] ?: ucfirst($type)) ?></span>
                        <?php elseif(!empty($t['period_no'])): ?>
                            Period <?= (int)$t['period_no'] ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;"><?= date('H:i', strtotime($t['start_time'])) ?> - <?= date('H:i', strtotime($t['end_time'])) ?></td>
                    <td class="fw-600"><?= htmlspecialchars($t['course_name'] ?? ($isBreak ? ($t['label'] ?: ucfirst($type)) : 'Free period')) ?></td>
                    <td><?= $isBreak ? '—' : htmlspecialchars($t['teacher_name'] ?? 'N/A') ?></td>
                    <td><?= $isBreak ? '—' : htmlspecialchars($t['room'] ?: 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($timetable)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No timetable entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">My Academic Schedule</div></div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php $currentDay = ''; foreach($timetable as $t): ?>
                <tr>
                    <td class="fw-700">
                        <?php if($t['day'] !== $currentDay): $currentDay = $t['day']; echo $currentDay; endif; ?>
                    </td>
                    <td><?= date('H:i', strtotime($t['start_time'])) ?> - <?= date('H:i', strtotime($t['end_time'])) ?></td>
                    <td class="fw-600"><?= htmlspecialchars($t['course_name']) ?></td>
                    <td><?= htmlspecialchars($t['teacher_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($t['room'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($timetable)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No timetable entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

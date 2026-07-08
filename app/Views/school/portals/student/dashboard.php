<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Welcome, <?= htmlspecialchars($student['name']) ?></div>
    <div class="text-muted">Class: <?= htmlspecialchars($student['class_name'] ?? 'Not Assigned') ?></div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Attendance Rate</div>
        <div class="stat-value">
            <?php 
                $rate = $attendance['total'] > 0 ? round(($attendance['present'] / $attendance['total']) * 100) : 0;
                echo $rate . '%';
            ?>
        </div>
        <div class="stat-sub"><?= $attendance['present'] ?> / <?= $attendance['total'] ?> days</div>
    </div>
    <div class="stat-card" style="--card-color: var(--success);">
        <div class="stat-label">Average Mark</div>
        <div class="stat-value">78%</div>
        <div class="stat-sub">Based on last 5 exams</div>
    </div>
</div>

<div style="display:grid;grid-template-columns: 2fr 1fr; gap:20px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Recent Exam Results</div></div>
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
                    <?php foreach($recentGrades as $g): ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($g['course_name']) ?></td>
                        <td><?= htmlspecialchars($g['exam_name']) ?></td>
                        <td><?= $g['marks_obtained'] ?> / <?= $g['total_marks'] ?></td>
                        <td><span class="badge badge-primary"><?= $g['grade_letter'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><div class="card-title">Today's Schedule</div></div>
        <div class="card-body">
            <div class="text-muted text-center" style="padding:20px;">No classes scheduled for today.</div>
        </div>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

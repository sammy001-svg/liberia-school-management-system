<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">My Academic Results</div></div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Exam / Assessment</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($grades as $g): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($g['course_name']) ?></td>
                    <td><?= htmlspecialchars($g['exam_name']) ?></td>
                    <td class="fw-700"><?= $g['marks_obtained'] ?></td>
                    <td><?= $g['total_marks'] ?></td>
                    <td><span class="badge badge-primary"><?= $g['grade_letter'] ?></span></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($g['remarks']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($grades)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No grades recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

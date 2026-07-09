<?php
$pct = $attempt['total_marks'] > 0 ? round($attempt['score'] / $attempt['total_marks'] * 100, 1) : 0;
$grade = $pct>=90?'A+':($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':($pct>=50?'D':'F'))));
require ROOT_DIR . '/app/Views/layouts/header.php';
?>
<div class="page-header"><div class="page-header-title">Result — <?= htmlspecialchars($exam['title']) ?></div></div>

<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;">
    <div style="text-align:center;">
      <div style="font-size:42px;font-weight:800;color:<?= $pct>=50?'var(--success)':'var(--danger)' ?>;"><?= $pct ?>%</div>
      <div class="text-muted" style="font-size:12px;">Overall Score</div>
    </div>
    <div class="mini-stat-grid" style="flex:1;">
      <div class="mini-stat">
        <div class="mini-stat-value"><?= number_format($attempt['score'],1) ?> / <?= number_format($attempt['total_marks'],1) ?></div>
        <div class="mini-stat-label">Marks Obtained</div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-value"><span class="badge badge-<?= $grade==='F'?'danger':'success' ?>"><?= $grade ?></span></div>
        <div class="mini-stat-label">Grade</div>
      </div>
      <div class="mini-stat">
        <div class="mini-stat-value"><?= date('M d, Y H:i', strtotime($attempt['submitted_at'])) ?></div>
        <div class="mini-stat-label">Submitted</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Answer Breakdown</div></div>
  <div class="card-body" style="padding:0;">
    <?php foreach($breakdown as $i => $q): ?>
    <div style="padding:18px 22px;<?= $i>0 ? 'border-top:1px solid var(--border);' : '' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
        <div style="flex:1;">
          <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Question <?= $i+1 ?> · <?= number_format($q['marks'],1) ?> mark<?= $q['marks']!=1?'s':'' ?></div>
          <div class="fw-600" style="margin-top:4px;"><?= htmlspecialchars($q['question_text']) ?></div>
          <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:13px;">
            <?php foreach(['a'=>$q['option_a'],'b'=>$q['option_b'],'c'=>$q['option_c'],'d'=>$q['option_d']] as $key=>$opt): ?>
              <?php if($opt !== null && $opt !== ''):
                $isCorrectOpt = $q['correct_option']===$key;
                $isSelected = $q['selected_option']===$key;
                $style = 'color:var(--text-light);';
                if ($isCorrectOpt) { $style = 'background:rgba(16,185,129,0.12);color:var(--success);font-weight:600;'; }
                if ($isSelected && !$isCorrectOpt) { $style = 'background:rgba(239,68,68,0.12);color:var(--danger);font-weight:600;'; }
              ?>
              <div style="padding:6px 10px;border-radius:6px;<?= $style ?>">
                <?= strtoupper($key) ?>. <?= htmlspecialchars($opt) ?>
                <?= $isCorrectOpt ? ' ✓' : '' ?><?= $isSelected && !$isCorrectOpt ? ' ✗ (your answer)' : '' ?>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if(!$q['selected_option']): ?><div style="margin-top:8px;font-size:12px;color:var(--warning);">You did not answer this question.</div><?php endif; ?>
        </div>
        <div><span class="badge badge-<?= $q['is_correct'] ? 'success' : 'danger' ?>"><?= $q['is_correct'] ? 'Correct' : 'Incorrect' ?></span></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

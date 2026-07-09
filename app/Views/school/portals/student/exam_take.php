<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<style>
  .exam-timer-bar { position:sticky; top:0; z-index:20; background:var(--card-bg,#fff); border:1px solid var(--border); border-radius:12px; padding:16px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
  .exam-timer { font-family:monospace; font-size:22px; font-weight:800; letter-spacing:0.02em; padding:6px 16px; border-radius:8px; background:rgba(16,185,129,0.12); color:var(--success); }
  .exam-timer.exam-timer-warn { background:rgba(239,68,68,0.14); color:var(--danger); }
  .exam-q-card { padding:20px 22px; border-bottom:1px solid var(--border); }
  .exam-q-card:last-child { border-bottom:none; }
  .exam-q-label { font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.04em; }
  .exam-option { display:flex; align-items:center; gap:10px; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px; margin-top:8px; cursor:pointer; transition:border-color .15s, background .15s; }
  .exam-option:hover { border-color:var(--primary); }
  .exam-option input:checked + span { font-weight:600; }
  .exam-option:has(input:checked) { border-color:var(--primary); background:rgba(16,185,129,0.06); }
</style>

<div class="exam-timer-bar">
  <div>
    <div class="page-header-title" style="margin:0;"><?= htmlspecialchars($exam['title']) ?></div>
    <div class="text-muted" style="font-size:12px;"><?= count($questions) ?> question(s) · <?= number_format(array_sum(array_column($questions,'marks')),0) ?> marks total</div>
  </div>
  <div class="exam-timer" id="examTimer">--:--</div>
</div>

<form method="POST" action="<?= $cfg['url'] ?>/student/exams/<?= $exam['id'] ?>/submit" id="examForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <?php foreach($questions as $i => $q): ?>
    <div class="exam-q-card">
      <div class="exam-q-label">Question <?= $i+1 ?> of <?= count($questions) ?> · <?= number_format($q['marks'],1) ?> mark<?= $q['marks']!=1?'s':'' ?></div>
      <div class="fw-600" style="margin-top:6px;font-size:15px;"><?= htmlspecialchars($q['question_text']) ?></div>
      <div>
        <?php foreach(['a'=>$q['option_a'],'b'=>$q['option_b'],'c'=>$q['option_c'],'d'=>$q['option_d']] as $key=>$opt): ?>
          <?php if($opt !== null && $opt !== ''): ?>
          <label class="exam-option">
            <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $key ?>" <?= (($answerMap[$q['id']] ?? null) === $key) ? 'checked' : '' ?>>
            <span><?= strtoupper($key) ?>. <?= htmlspecialchars($opt) ?></span>
          </label>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top:20px;display:flex;justify-content:flex-end;">
    <button type="submit" class="btn btn-primary btn-lg" id="examSubmitBtn">Submit Exam</button>
  </div>
</form>

<script>
(function(){
  var deadline = <?= (int)$deadline ?> * 1000;
  var timerEl = document.getElementById('examTimer');
  var form = document.getElementById('examForm');
  var submitted = false;

  function tick() {
    var remaining = deadline - Date.now();
    if (remaining <= 0) {
      timerEl.textContent = '00:00';
      if (!submitted) { submitted = true; form.submit(); }
      return;
    }
    var totalSec = Math.floor(remaining / 1000);
    var h = Math.floor(totalSec / 3600);
    var m = Math.floor((totalSec % 3600) / 60);
    var s = totalSec % 60;
    timerEl.textContent = (h > 0 ? String(h).padStart(2,'0') + ':' : '') + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if (remaining < 60000) { timerEl.classList.add('exam-timer-warn'); }
    setTimeout(tick, 1000);
  }
  tick();

  form.addEventListener('submit', function(e){
    if (submitted) return;
    if (!confirm('Submit your exam now? You cannot change your answers after submitting.')) {
      e.preventDefault();
      return;
    }
    submitted = true;
    document.getElementById('examSubmitBtn').disabled = true;
    document.getElementById('examSubmitBtn').textContent = 'Submitting…';
  });

  window.addEventListener('beforeunload', function(e){
    if (!submitted) { e.preventDefault(); e.returnValue = ''; }
  });
})();
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>

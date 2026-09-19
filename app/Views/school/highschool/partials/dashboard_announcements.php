<div style="flex:1;overflow-y:auto;">
  <?php if (!empty($announcements)): foreach ($announcements as $a): ?>
    <div class="list-item">
      <div class="list-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
      </div>
      <div class="list-content">
        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
          <h5><?= htmlspecialchars($a['title']) ?></h5>
          <span class="list-date"><?= date('M d, Y', strtotime($a['published_at'])) ?></span>
        </div>
        <p><?= htmlspecialchars(mb_strimwidth($a['content'] ?? '', 0, 90, '…')) ?></p>
      </div>
    </div>
  <?php endforeach; else: ?>
    <div class="empty-note">No announcements yet.</div>
  <?php endif; ?>
</div>

<div class="calendar">
  <?php foreach ([1, 2, 3] as $i => $n): ?>
    <article class="term" data-reveal data-reveal-delay="<?= $i ?>">
      <span class="term-label"><?= ['Before the year', 'First Semester', 'Second Semester'][$i] ?></span>
      <h3><?= $t("site.cal{$n}_title") ?></h3>
      <span class="term-months"><?= wicon('calendar') ?> <?= $t("site.cal{$n}_months") ?></span>
      <ul>
        <?php foreach ($lines("site.cal{$n}_items") as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?>
      </ul>
    </article>
  <?php endforeach; ?>
</div>

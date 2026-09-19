<?php
// The four divisions, in school order: content prefix => public page.
$divisionPages = $divisionPages ?? ['early' => 'early-childhood', 'elementary' => 'elementary', 'junior' => 'junior-high', 'senior' => 'senior-high'];
?>
<div class="division-grid">
  <?php $i = 0; foreach ($divisionPages as $key => $href): ?>
    <a class="division-card" href="<?= $url($href) ?>" data-reveal data-reveal-delay="<?= $i++ ?>">
      <img src="<?= $im("$key.card_image") ?>" alt="" loading="lazy">
      <div class="division-body">
        <span class="division-grade"><?= $t("$key.card_grade") ?></span>
        <h3><?= $t("$key.card_title") ?></h3>
        <?= $paras("$key.card_text") ?>
        <span class="text-link">Explore <?= wicon('arrow-right') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

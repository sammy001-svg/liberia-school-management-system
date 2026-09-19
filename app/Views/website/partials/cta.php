<?php
// Uses the site-wide enroll banner unless the page sets $ctaKey to its own prefix.
$ctaKey = $ctaKey ?? 'site';
?>
<section class="section section--tight">
  <div class="container">
    <div class="cta-band" data-reveal>
      <img src="<?= $im('site.cta_image') ?>" alt="" loading="lazy">
      <div>
        <span class="eyebrow eyebrow--light">Admissions Open</span>
        <h2 class="display display--light"><?= $h("$ctaKey.cta_title") ?></h2>
        <?= $paras("$ctaKey.cta_text") ?>
      </div>
      <div class="cta-actions">
        <a href="<?= $url('apply') ?>" class="btn btn-accent btn-lg">Enroll Now <?= wicon('arrow-right') ?></a>
        <?php if (($activeNav ?? '') === 'admissions'): ?>
          <a href="<?= $telHref($site['phones'][0]) ?>" class="btn btn-outline-light btn-lg"><?= wicon('phone') ?> Call Us</a>
        <?php else: ?>
          <a href="<?= $url('admissions') ?>" class="btn btn-outline-light btn-lg">Admissions &amp; Fees</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

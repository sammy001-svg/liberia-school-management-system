<?php
$ctaTitle = $ctaTitle ?? 'Begin your child’s educational journey <em>today!</em>';
$ctaText  = $ctaText  ?? 'Start your child’s enrollment online, then complete the process on campus. It’s easy and fast — and space is limited.';
?>
<section class="section section--tight">
  <div class="container">
    <div class="cta-band" data-reveal>
      <img src="<?= $img($ctaImage ?? 'assembly-panorama.jpg') ?>" alt="" loading="lazy">
      <div>
        <span class="eyebrow eyebrow--light">Admissions Open</span>
        <h2 class="display display--light"><?= $ctaTitle ?></h2>
        <p><?= $ctaText ?></p>
      </div>
      <div class="cta-actions">
        <a href="<?= $url('apply') ?>" class="btn btn-gold btn-lg">Enroll Now <?= wicon('arrow-right') ?></a>
        <?php if (($activeNav ?? '') === 'admissions'): ?>
          <a href="<?= $telHref($site['phones'][0]) ?>" class="btn btn-outline-light btn-lg"><?= wicon('phone') ?> Call Us</a>
        <?php else: ?>
          <a href="<?= $url('admissions') ?>" class="btn btn-outline-light btn-lg">Admissions &amp; Fees</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

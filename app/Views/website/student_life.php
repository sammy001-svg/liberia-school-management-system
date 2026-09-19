<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'student_life'; $crumbs = ['About' => $url('about-us'), 'Student Life' => null];
$heroChipIcons = ['users', 'utensils', 'graduation'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Third Place Initiative</span>
      <h2 class="display"><?= $h('student_life.intro_title') ?></h2>
      <?= $paras('student_life.intro_body') ?>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $im('student_life.intro_image1') ?>" alt="" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $im('student_life.intro_image2') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Beyond the Classroom</span>
      <h2 class="display"><?= $h('student_life.programs_title') ?></h2>
    </div>
    <div class="card-grid-3">
      <?php $progIcons = [['users', ''], ['utensils', 'icon-badge--green'], ['graduation', 'icon-badge--accent'], ['monitor', 'icon-badge--accent'], ['hand', ''], ['flag', 'icon-badge--green']]; ?>
      <?php foreach ($list('student_life.programs') as $i => [$title, $text]): [$icon, $cls] = $progIcons[$i % 6]; ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Gallery</span>
      <h2 class="display"><?= $h('student_life.gallery_title') ?></h2>
    </div>
    <?php $galleryItems = [
        ['assembly-panorama.jpg', 'The whole school gathered for assembly', 'gallery-item--big'],
        ['students-walk.jpg', 'Students walking together on campus', 'gallery-item--wide'],
        ['assembly-courtyard.jpg', 'Morning assembly in the courtyard', ''],
        ['students-lineup.jpg', 'Students in uniform', ''],
        ['hero-assembly.jpg', 'Students at assembly', 'gallery-item--wide'],
        ['gallery-5.jpg', 'Students exploring technology', ''],
        ['gallery-2.jpg', 'Students in class', ''],
    ]; require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow eyebrow--light">Through the Year</span>
      <h2 class="display display--light"><?= $h('student_life.calendar_title') ?></h2>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

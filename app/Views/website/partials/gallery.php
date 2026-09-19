<?php
// Real CELDI photos lead; the rest are images the school chose for its ClickSites gallery.
$galleryItems = $galleryItems ?? [
    ['assembly-courtyard.jpg', 'CELDI students at morning assembly', 'gallery-item--big'],
    ['students-lineup.jpg', 'CELDI students in uniform', 'gallery-item--wide'],
    ['students-walk.jpg', 'Students walking across campus', ''],
    ['gallery-5.jpg', 'Students exploring technology together', ''],
    ['assembly-panorama.jpg', 'The whole school gathered for assembly', 'gallery-item--wide'],
    ['gallery-3.jpg', 'One-on-one mentoring', ''],
    ['gallery-1.jpg', 'Students in class', ''],
];
?>
<div class="gallery" data-reveal>
  <?php foreach ($galleryItems as [$file, $alt, $cls]): ?>
    <button type="button" class="gallery-item <?= $cls ?>" data-lightbox-src="<?= $img($file) ?>" aria-label="<?= htmlspecialchars($alt) ?>">
      <img src="<?= $img($file) ?>" alt="<?= htmlspecialchars($alt) ?>" loading="lazy">
    </button>
  <?php endforeach; ?>
</div>

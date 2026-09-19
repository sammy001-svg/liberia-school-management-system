<?php
// Photos the school uploads under Website → Gallery replace the built-in set entirely.
// The size pattern repeats every seven photos so any number still makes a tidy mosaic.
if (!empty($galleryPhotos)) {
    $galleryPattern = ['gallery-item--big', 'gallery-item--wide', '', '', 'gallery-item--wide', '', ''];
    $galleryItems = [];
    foreach ($galleryPhotos as $i => $photo) {
        $galleryItems[] = [htmlspecialchars($photo['image_url']), (string)($photo['caption'] ?? ''), $galleryPattern[$i % 7]];
    }
} else {
    $galleryItems = array_map(fn($g) => [$img($g[0]), $g[1], $g[2]], $galleryItems ?? [
        ['assembly-courtyard.jpg', 'CELDI students at morning assembly', 'gallery-item--big'],
        ['students-lineup.jpg', 'CELDI students in uniform', 'gallery-item--wide'],
        ['students-walk.jpg', 'Students walking across campus', ''],
        ['gallery-5.jpg', 'Students exploring technology together', ''],
        ['assembly-panorama.jpg', 'The whole school gathered for assembly', 'gallery-item--wide'],
        ['gallery-3.jpg', 'One-on-one mentoring', ''],
        ['gallery-1.jpg', 'Students in class', ''],
    ]);
}
?>
<div class="gallery" data-reveal>
  <?php foreach ($galleryItems as [$src, $alt, $cls]): ?>
    <button type="button" class="gallery-item <?= $cls ?>" data-lightbox-src="<?= $src ?>" aria-label="<?= htmlspecialchars($alt ?: 'View photo') ?>">
      <img src="<?= $src ?>" alt="<?= htmlspecialchars($alt) ?>" loading="lazy">
    </button>
  <?php endforeach; ?>
</div>

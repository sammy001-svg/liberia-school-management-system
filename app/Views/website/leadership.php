<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'leadership'; $crumbs = ['About' => $url('about-us'), 'Our Leadership' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<?php if ($raw('leadership.statement') !== ''): ?>
<section class="section section--tight section--lilac">
  <div class="container">
    <div class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      <?= $h('leadership.statement') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/**
 * One section per group, in the school's order: Administration, Board, PTA, Faculty.
 * Each reads the same way — the written introduction first, then the photographs of
 * the people in it.
 *   photos    the group key on each profile (Website → Leadership Team)
 *   text      the matching wording in the CMS (Website → Pages → Our Leadership)
 */
$sections = [
    ['photos' => 'leadership', 'text' => 'admin', 'kicker' => 'Administration',  'icon' => 'compass',  'badge' => 'icon-badge--green',  'fallback' => $raw('leadership.team_title') ?: 'School Leadership', 'tone' => ''],
    ['photos' => 'board',      'text' => 'board', 'kicker' => 'Governance',      'icon' => 'building', 'badge' => '',                   'fallback' => 'Board Members',       'tone' => 'section--cream'],
    ['photos' => 'pta',        'text' => 'pta',   'kicker' => 'Parents',         'icon' => 'heart',    'badge' => '',                   'fallback' => 'PTA Leadership',      'tone' => ''],
    ['photos' => 'faculty',    'text' => 'staff', 'kicker' => 'Faculty & Staff', 'icon' => 'users',    'badge' => 'icon-badge--accent', 'fallback' => 'Our Faculty',         'tone' => 'section--lilac'],
];
$allGroups = ($groups ?? []) + ['leadership' => $leaders ?? []];
?>

<?php foreach ($sections as $sec):
    $people   = $allGroups[$sec['photos']] ?? [];
    $title    = $raw("leadership.{$sec['text']}_title");
    $body     = $raw("leadership.{$sec['text']}_body");
    if (!$people && $title === '' && $body === '') { continue; }
?>
  <section class="section <?= $sec['tone'] ?>">
    <div class="container">
      <?php if ($title !== '' || $body !== ''): ?>
        <div class="tier" data-reveal>
          <div class="tier-head">
            <span class="icon-badge <?= $sec['badge'] ?>"><?= wicon($sec['icon']) ?></span>
            <span class="tier-kicker"><?= htmlspecialchars($sec['kicker']) ?></span>
            <h3><?= $title !== '' ? $t("leadership.{$sec['text']}_title") : htmlspecialchars($sec['fallback']) ?></h3>
          </div>
          <?php if ($body !== ''): ?>
            <div class="tier-body">
              <?php foreach (preg_split('/\R\s*\R/', trim($body)) as $n => $para): ?>
                <p<?= $n === 0 ? ' class="lead" style="margin-top:0;"' : '' ?>><?= nl2br($fmt(trim($para)), false) ?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="section-head section-head--center" data-reveal>
          <span class="eyebrow eyebrow--center"><?= htmlspecialchars($sec['kicker']) ?></span>
          <h2 class="display"><?= htmlspecialchars($sec['fallback']) ?></h2>
        </div>
      <?php endif; ?>

      <?php if ($people): ?>
        <div class="team-grid" style="margin-top:28px;">
          <?php foreach ($people as $i => $leader): ?>
            <article class="team-card" data-reveal data-reveal-delay="<?= $i % 4 ?>">
              <div class="team-photo">
                <?php if (!empty($leader['photo_url'])): ?>
                  <img src="<?= htmlspecialchars($leader['photo_url']) ?>" alt="<?= htmlspecialchars($leader['name']) ?>" loading="lazy">
                <?php else: ?>
                  <span class="team-initials"><?= htmlspecialchars(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($leader['name'])), 0, 2)))) ?></span>
                <?php endif; ?>
              </div>
              <div class="team-body">
                <h3><?= htmlspecialchars($leader['name']) ?></h3>
                <span class="team-role"><?= htmlspecialchars($leader['position']) ?></span>
                <?php if (!empty($leader['bio'])): ?><p><?= nl2br(htmlspecialchars($leader['bio']), false) ?></p><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php require __DIR__ . '/partials/values.php'; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

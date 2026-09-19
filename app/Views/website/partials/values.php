<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">Our Core Values</span>
      <h2 class="display display--light">The pillars behind <em>every</em> CELDI student</h2>
    </div>
    <div class="values-grid" data-reveal>
      <?php foreach ([
        ['award', 'Excellence', 'We pursue the highest standards in all academic and co-curricular endeavors.'],
        ['shield', 'Integrity', 'We foster honesty, transparency, and ethical conduct in every student.'],
        ['heart', 'Respect', 'We honor the dignity of every individual, regardless of background or ability.'],
        ['hand', 'Service', 'We cultivate a spirit of giving back to our community, county, and nation.'],
        ['lightbulb', 'Innovation', 'We embrace creative thinking and 21st-century skills for a changing world.'],
        ['flag', 'Patriotism', 'We instill pride in Liberian heritage, culture, and civic responsibility.'],
      ] as [$icon, $title, $text]): ?>
        <div class="value">
          <?= wicon($icon) ?>
          <h3><?= $title ?></h3>
          <p><?= $text ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="pillar-list" data-reveal>
      <?php foreach (['Excellence', 'Integrity', 'Respect', 'Service', 'Innovation', 'Leadership', 'Discipline', 'Patriotism'] as $p): ?>
        <span><?= $p ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

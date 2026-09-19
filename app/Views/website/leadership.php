<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'community-team.jpg',
    'eyebrow' => 'Our Leadership',
    'title'   => 'Leading by <em>serving</em>.',
    'lead'    => 'At CELDI Academy, leadership is a calling to serve: our board, administration, faculty and parents work together for every child.',
    'crumbs'  => ['About' => $url('about-us'), 'Our Leadership' => null],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section section--tight section--lilac">
  <div class="container">
    <p class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      We are developing future leaders who are proactive and serve others — and that begins with <em>how we lead</em> ourselves.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="tier" data-reveal>
      <div class="tier-head">
        <span class="icon-badge"><?= wicon('building') ?></span>
        <span class="tier-kicker">Governance</span>
        <h3>The Board</h3>
      </div>
      <div class="tier-body">
        <p class="lead" style="margin-top:0;">The Board provides the vision and stewardship that keep CELDI Academy faithful to its mission: a Christ-centered, technological, and vocational education that equips students to be critical thinkers, entrepreneurs, and servant leaders.</p>
        <p>CELDI Academy is an affiliate ministry of UrbanPromise International, and its leadership is committed to the long-term growth and prosperity of the students and communities it serves.</p>
      </div>
    </div>

    <div class="tier" data-reveal>
      <div class="tier-head">
        <span class="icon-badge icon-badge--green"><?= wicon('compass') ?></span>
        <span class="tier-kicker">Administration</span>
        <h3>School Leadership</h3>
      </div>
      <div class="tier-body">
        <p class="lead" style="margin-top:0;">The school’s administration leads the day-to-day life of the Academy across all four divisions — Early Childhood &amp; Daycare, Elementary, Junior High, and Senior High.</p>
        <p>They set ambitious goals to increase graduation and success rates, uphold the Academy’s commitment to professional ethics and mentorship, and oversee the “chapters” model through which every student earns a Leadership Development Certificate.</p>
      </div>
    </div>

    <div class="tier" data-reveal>
      <div class="tier-head">
        <span class="icon-badge icon-badge--gold"><?= wicon('users') ?></span>
        <span class="tier-kicker">Faculty &amp; Staff</span>
        <h3>Our Educators</h3>
      </div>
      <div class="tier-body">
        <p class="lead" style="margin-top:0;">The strength of our academy lies in our educators. We don’t just hire teachers; we develop mentors.</p>
        <p>Our staff undergo regular training and professional workshops to stay at the forefront of modern pedagogy. Through partnerships with global organizations, our teachers benefit from cross-cultural training and specialized seminars, ensuring every child receives instruction that meets international standards of excellence.</p>
      </div>
    </div>

    <div class="tier" data-reveal>
      <div class="tier-head">
        <span class="icon-badge"><?= wicon('heart') ?></span>
        <span class="tier-kicker">Parents</span>
        <h3>Parent-Teacher Association</h3>
      </div>
      <div class="tier-body">
        <p class="lead" style="margin-top:0;">Education is a partnership between the school and the home.</p>
        <p>The PTA meets three times each academic year — once in the first semester and twice in the second — so that parents and teachers can work together to monitor progress and celebrate the growth of every student.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/values.php'; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

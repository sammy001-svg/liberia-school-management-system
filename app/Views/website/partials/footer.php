</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-affiliate">
      <img src="<?= $img('urbanpromise-logo.png') ?>" alt="UrbanPromise International" loading="lazy" width="240" height="48">
      <p>CELDI Academy is an affiliate ministry of <strong>UrbanPromise International</strong>.</p>
    </div>

    <div class="footer-grid">
      <div class="footer-brand">
        <a class="brand brand--light" href="<?= $url() ?>">
          <img src="<?= $img('celdi-logo.png') ?>" alt="" width="52" height="66" loading="lazy">
          <span class="brand-text">
            <span class="brand-name"><?= htmlspecialchars($site['name']) ?></span>
            <span class="brand-sub"><?= htmlspecialchars($site['tagline']) ?></span>
          </span>
        </a>
        <p class="footer-motto">“<?= htmlspecialchars($site['motto']) ?>”</p>
      </div>

      <div>
        <h3 class="footer-title">Explore</h3>
        <ul class="footer-links">
          <li><a href="<?= $url('about-us') ?>">About Us</a></li>
          <li><a href="<?= $url('our-leadership') ?>">Our Leadership</a></li>
          <li><a href="<?= $url('student-life') ?>">Student Life</a></li>
          <li><a href="<?= $url('academy-news') ?>">News &amp; Events</a></li>
          <li><a href="<?= $url('admissions') ?>">Admissions &amp; Fees</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer-title">Divisions</h3>
        <ul class="footer-links">
          <li><a href="<?= $url('early-childhood') ?>">Early Childhood &amp; Daycare</a></li>
          <li><a href="<?= $url('elementary') ?>">Elementary · Grades 1–6</a></li>
          <li><a href="<?= $url('junior-high') ?>">Junior High · Grades 7–9</a></li>
          <li><a href="<?= $url('senior-high') ?>">Senior High · Grades 10–12</a></li>
        </ul>
      </div>

      <div>
        <h3 class="footer-title">Contact</h3>
        <ul class="footer-contact">
          <li><?= wicon('map-pin') ?><span><?= htmlspecialchars($site['address']) ?></span></li>
          <li><?= wicon('phone') ?><span>
            <?php foreach ($site['phones'] as $i => $phone): ?>
              <a href="<?= $telHref($phone) ?>"><?= htmlspecialchars($phone) ?></a><?= $i < count($site['phones']) - 1 ? '<br>' : '' ?>
            <?php endforeach; ?>
          </span></li>
          <?php if ($site['email'] !== ''): ?>
            <li><?= wicon('mail') ?><a href="mailto:<?= htmlspecialchars($site['email']) ?>"><?= htmlspecialchars($site['email']) ?></a></li>
          <?php endif; ?>
        </ul>
        <div class="footer-cta">
          <a href="<?= $url('apply') ?>" class="btn btn-gold btn-sm">Apply Online</a>
          <a href="<?= $url('login') ?>" class="btn btn-outline-light btn-sm"><?= $portalLabel ?></a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. All rights reserved.</span>
      <span>Ben Town · Margibi County · Liberia</span>
    </div>
  </div>
</footer>

<div class="lightbox" hidden data-lightbox>
  <button class="lightbox-close" type="button" aria-label="Close" data-lightbox-close><?= wicon('close') ?></button>
  <img alt="" data-lightbox-img>
</div>

<script src="<?= $base ?>/assets/website/site.js?v=<?= $assetVer ?>" defer></script>
</body>
</html>

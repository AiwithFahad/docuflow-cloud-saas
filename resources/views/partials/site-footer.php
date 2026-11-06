</main>
<footer class="site-footer mt-0">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="brand text-white text-decoration-none" href="<?= url('') ?>"><span class="brand-mark"><i class="bi bi-cloud-check-fill"></i></span> DocuFlow Cloud</a>
        <p class="mt-3 text-light-50">Secure cloud document management for modern teams — organize, share, approve and version every file, with full tenant isolation and audit trails.</p>
        <form method="post" action="<?= url('newsletter') ?>" class="d-flex gap-2 mt-3">
          <?= csrf_field() ?>
          <input type="email" name="email" class="form-control" placeholder="Work email for our newsletter" required>
          <button class="btn btn-purple" type="submit">Join</button>
        </form>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Product</h6>
        <ul class="footer-links">
          <li><a href="<?= url('features') ?>">Features</a></li>
          <li><a href="<?= url('pricing') ?>">Pricing</a></li>
          <li><a href="<?= url('integrations') ?>">Integrations</a></li>
          <li><a href="<?= url('security') ?>">Security</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Solutions</h6>
        <ul class="footer-links">
          <li><a href="<?= url('solutions') ?>">Solutions</a></li>
          <li><a href="<?= url('industries') ?>">Industries</a></li>
          <li><a href="<?= url('about') ?>">About us</a></li>
          <li><a href="<?= url('blog') ?>">Blog</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Company</h6>
        <ul class="footer-links">
          <li><a href="<?= url('contact') ?>">Contact</a></li>
          <li><a href="<?= url('register') ?>">Start a company</a></li>
          <li><a href="<?= url('login') ?>">Sign in</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Legal</h6>
        <ul class="footer-links">
          <li><a href="<?= url('privacy') ?>">Privacy policy</a></li>
          <li><a href="<?= url('terms') ?>">Terms of service</a></li>
        </ul>
      </div>
    </div>
    <hr class="footer-hr">
    <div class="d-flex flex-column flex-md-row justify-content-between small text-light-50">
      <span>© <?= date('Y') ?> DocuFlow Cloud. All rights reserved.</span>
      <span>Built with Core PHP 8 · MySQL · Bootstrap 5</span>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/site.js') ?>"></script>
</body>
</html>

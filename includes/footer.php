<?php
// includes/footer.php
?>
</div>
<!-- End Main Content Container -->

<!-- Simple Footer -->
<footer class="bg-dark border-top border-secondary py-4 mt-5">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="text-muted small mb-0">
          <i class="bi bi-c-circle text-primary me-1"></i>
          <?= date('Y') ?> JobVault. Todos os direitos reservados.
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="text-muted small mb-0">
          Desenvolvido com <i class="bi bi-heart-fill text-danger me-1"></i> em PHP + Bootstrap 5
        </p>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Toggle password visibility
  document.getElementById('toggle-password-login')?.addEventListener('click', function() {
    const input = document.getElementById('senha-login');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye-fill');
      icon.classList.add('bi-eye-slash-fill');
    } else {
      input.type = 'password';
      icon.classList.remove('bi-eye-slash-fill');
      icon.classList.add('bi-eye-fill');
    }
  });

  document.getElementById('toggle-password-signup')?.addEventListener('click', function() {
    const input = document.getElementById('senha-signup');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye-fill');
      icon.classList.add('bi-eye-slash-fill');
    } else {
      input.type = 'password';
      icon.classList.remove('bi-eye-slash-fill');
      icon.classList.add('bi-eye-fill');
    }
  });

  // Form validation
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', function(e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      this.classList.add('was-validated');
    }, false);
  });

  // Close alerts automatically
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });
</script>

</body>

</html>
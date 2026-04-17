<?php
?>
</main>

<footer class="app-footer py-4">
  <div class="container-content">
    <div class="row g-2 align-items-center">
      <div class="col-md-6">
        <p class="text-muted small mb-0">
          <i class="bi bi-c-circle me-1"></i>
          <?= date('Y') ?> JobVault. Todos os direitos reservados.
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="text-muted small mb-0">
          Plataforma de vagas em PHP + Bootstrap 5
        </p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.getElementById('toggle-password-login')?.addEventListener('click', function() {
    const input = document.getElementById('password-login');
    const icon = this.querySelector('i');
    if (!input || !icon) {
      return;
    }

    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye-fill');
      icon.classList.add('bi-eye-slash-fill');
      this.setAttribute('aria-label', 'Ocultar senha');
      return;
    }

    input.type = 'password';
    icon.classList.remove('bi-eye-slash-fill');
    icon.classList.add('bi-eye-fill');
    this.setAttribute('aria-label', 'Mostrar senha');
  });

  document.getElementById('toggle-password-signup')?.addEventListener('click', function() {
    const input = document.getElementById('password-signup');
    const icon = this.querySelector('i');
    if (!input || !icon) {
      return;
    }

    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye-fill');
      icon.classList.add('bi-eye-slash-fill');
      this.setAttribute('aria-label', 'Ocultar senha');
      return;
    }

    input.type = 'password';
    icon.classList.remove('bi-eye-slash-fill');
    icon.classList.add('bi-eye-fill');
    this.setAttribute('aria-label', 'Mostrar senha');
  });

  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', function(e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      this.classList.add('was-validated');
    }, false);
  });

  document.querySelectorAll('.alert[data-auto-close="true"]').forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });
</script>

</body>

</html>

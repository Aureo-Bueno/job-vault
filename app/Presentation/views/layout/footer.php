<?php
?>
</main>

<footer class="app-footer py-4">
  <div class="container-content">
    <div class="row g-2 align-items-center">
      <div class="col-12">
        <p class="text-muted small mb-0">
          <i class="bi bi-c-circle me-1"></i>
          <?= date('Y') ?> JobVault. Todos os direitos reservados.
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

  // Client-side validation only highlights errors (red). We never paint fields green:
  // "valid format" does not mean the server accepted the data (e.g. wrong password).
  const markField = field => {
    const invalid = !field.checkValidity();
    field.classList.toggle('is-invalid', invalid);
    field.closest('.input-group')?.classList.toggle('is-invalid', invalid);
  };

  document.querySelectorAll('.needs-validation').forEach(form => {
    const fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');

    form.addEventListener('submit', function(e) {
      fields.forEach(markField);
      if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, false);

    fields.forEach(field => {
      const eventName = field.type === 'checkbox' || field.type === 'radio' ? 'change' : 'input';
      field.addEventListener(eventName, () => {
        if (field.classList.contains('is-invalid')) {
          markField(field);
        }
      });
    });
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

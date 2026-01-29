<?php
$erro = '';
?>

<div class="container-content">
  <div class="row mb-4">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-plus-circle-fill text-success"></i> Nova Vaga
      </h2>
      <p class="text-muted small mb-0">Preencha os dados para criar uma nova vaga</p>
    </div>
    <div class="col-auto">
      <a href="index.php">
        <button type="button" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Voltar
        </button>
      </a>
    </div>
  </div>

  <!-- Form Card -->
  <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
    <div class="card-body p-4">
      <form method="POST" action="" class="needs-validation" novalidate>

        <!-- Título -->
        <div class="mb-4">
          <label for="titulo" class="form-label text-white fw-bold">
            <i class="bi bi-file-earmark-text"></i> Título da Vaga
          </label>
          <input
            type="text"
            class="form-control form-control-lg"
            id="titulo"
            name="titulo"
            placeholder="Ex: Desenvolvedor PHP Senior"
            required>
          <div class="invalid-feedback text-danger d-block">
            Por favor, informe um título para a vaga.
          </div>
          <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Título deve ser claro e descritivo
          </small>
        </div>

        <!-- Descrição -->
        <div class="mb-4">
          <label for="descricao" class="form-label text-white fw-bold">
            <i class="bi bi-file-text"></i> Descrição da Vaga
          </label>
          <textarea
            class="form-control"
            id="descricao"
            name="descricao"
            rows="8"
            placeholder="Descreva os detalhes da vaga, responsabilidades, requisitos..."
            required
            style="resize: vertical; min-height: 150px;"></textarea>
          <div class="invalid-feedback text-danger d-block">
            Por favor, informe uma descrição para a vaga.
          </div>
          <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Seja detalhista para atrair candidatos qualificados
          </small>
        </div>

        <!-- Status -->
        <div class="mb-4">
          <label class="form-label text-white fw-bold">
            <i class="bi bi-toggle-on"></i> Status da Vaga
          </label>
          <div class="btn-group w-100" role="group">
            <input
              type="radio"
              class="btn-check"
              name="ativo"
              id="ativo-sim"
              value="s"
              checked>
            <label class="btn btn-outline-success fw-bold" for="ativo-sim">
              <i class="bi bi-check-circle-fill"></i> Ativa
            </label>

            <input
              type="radio"
              class="btn-check"
              name="ativo"
              id="ativo-nao"
              value="n">
            <label class="btn btn-outline-danger fw-bold" for="ativo-nao">
              <i class="bi bi-x-circle-fill"></i> Inativa
            </label>
          </div>
          <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Vagas ativas aparecem para candidatos
          </small>
        </div>

        <!-- Divider -->
        <hr class="bg-secondary opacity-25 my-4">

        <!-- Buttons -->
        <div class="d-flex gap-3 pt-2">
          <button
            type="submit"
            class="btn btn-success btn-lg fw-bold flex-grow-1"
            style="background: linear-gradient(135deg, #198754 0%, #20c997 100%); border: none;">
            <i class="bi bi-check-lg"></i> Criar Vaga
          </button>
          <a href="index.php" class="btn btn-outline-secondary btn-lg fw-bold">
            <i class="bi bi-x-lg"></i> Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Box -->
  <div class="alert alert-info alert-dismissible fade show mt-4" role="alert">
    <i class="bi bi-lightbulb-fill"></i>
    <strong>Dica:</strong> Preencha todos os campos corretamente para que sua vaga fique atrativa para os candidatos. Revise os dados antes de confirmar.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
</div>

<style>
  .form-control,
  .form-select,
  textarea {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    transition: all 0.3s ease;
    font-size: 1rem;
  }

  .form-control:focus,
  .form-select:focus,
  textarea:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(13, 110, 253, 0.5);
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  }

  .form-control::placeholder,
  textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
  }

  .form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
  }

  .form-label {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
  }

  .btn-group {
    gap: 0;
  }

  .btn-check:checked+.btn {
    color: #fff !important;
    font-weight: bold;
  }

  .btn-outline-success:not(.btn-check:checked + .btn) {
    border: 2px solid rgba(25, 135, 84, 0.3);
    color: rgba(25, 135, 84, 0.7) !important;
    transition: all 0.3s ease;
  }

  .btn-outline-success:not(.btn-check:checked + .btn):hover {
    border-color: rgba(25, 135, 84, 0.8);
    color: #20c997 !important;
  }

  .btn-outline-danger:not(.btn-check:checked + .btn) {
    border: 2px solid rgba(220, 53, 69, 0.3);
    color: rgba(220, 53, 69, 0.7) !important;
    transition: all 0.3s ease;
  }

  .btn-outline-danger:not(.btn-check:checked + .btn):hover {
    border-color: rgba(220, 53, 69, 0.8);
    color: #ff6b6b !important;
  }

  .btn-check:checked+.btn-outline-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border-color: transparent;
  }

  .btn-check:checked+.btn-outline-danger {
    background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
    border-color: transparent;
  }

  .btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
  }

  .btn-outline-secondary {
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
  }

  .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.4);
    color: #fff;
  }

  .card {
    border-radius: 12px;
  }

  .alert-info {
    background: rgba(13, 202, 240, 0.1);
    border: 1px solid rgba(13, 202, 240, 0.2);
    color: rgba(255, 255, 255, 0.85);
  }

  .alert-info .btn-close {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e");
  }

  hr {
    opacity: 0.2;
  }

  textarea {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.5;
  }

  .invalid-feedback {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.9rem;
  }
</style>

<script>
  // Form validation
  document.querySelector('form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    this.classList.add('was-validated');
  }, false);

  // Auto-close info alert after 10 seconds
  const infoAlert = document.querySelector('.alert-info');
  if (infoAlert) {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(infoAlert);
      bsAlert.close();
    }, 10000);
  }
</script>
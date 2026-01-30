<?php
$tituloFormulario = $tituloPagina ?? (defined('TITLE') ? TITLE : '');
?>

<div class="container-content p-5">
  <div class="row mb-4">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-plus-circle-fill text-primary"></i> <?= htmlspecialchars($tituloFormulario) ?>
      </h2>
      <p class="text-muted small mb-0">Preencha os dados para criar uma nova vaga</p>
    </div>
    <div class="col-auto">
      <a href="index.php?r=home">
        <button type="button" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Voltar
        </button>
      </a>
    </div>
  </div>

  <!-- Form Card -->
  <div class="card">
    <div class="card-body p-4">
      <form method="POST" class="needs-validation" novalidate>

        <!-- Título -->
        <div class="mb-4">
          <label for="title" class="form-label text-white fw-bold">
            <i class="bi bi-file-earmark-text"></i> Título da Vaga
          </label>
          <input
            type="text"
            class="form-control form-control-lg"
            id="title"
            name="title"
            placeholder="Ex: Desenvolvedor PHP Senior"
            value="<?= htmlspecialchars($vacancy->title ?? '') ?>"
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
          <label for="description" class="form-label text-white fw-bold">
            <i class="bi bi-file-text"></i> Descrição da Vaga
          </label>
          <textarea
            class="form-control"
            id="description"
            name="description"
            rows="8"
            placeholder="Descreva os detalhes da vaga, responsabilidades, requisitos..."
            required><?= htmlspecialchars($vacancy->description ?? '') ?></textarea>
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
              name="is_active"
              id="active-yes"
              value="s"
              <?= ($vacancy->isActive ?? 's') == 's' ? 'checked' : '' ?>>
            <label class="btn btn-outline-success fw-bold" for="active-yes">
              <i class="bi bi-check-circle-fill"></i> Ativa
            </label>

            <input
              type="radio"
              class="btn-check"
              name="is_active"
              id="active-no"
              value="n"
              <?= ($vacancy->isActive ?? '') == 'n' ? 'checked' : '' ?>>
            <label class="btn btn-outline-danger fw-bold" for="active-no">
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
            class="btn btn-primary btn-lg fw-bold flex-grow-1">
            <i class="bi bi-check-lg"></i> Enviar
          </button>
          <a href="index.php?r=home" class="btn btn-outline-secondary btn-lg fw-bold">
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

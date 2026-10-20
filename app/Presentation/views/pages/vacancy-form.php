<?php
// Expecting: $vacancy (object with title, description, isActive), $tituloPagina, $alerta
$tituloFormulario = $tituloPagina ?? 'Formulário de vaga';
$isActive = (bool) ($vacancy->isActive ?? true);
?>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-briefcase-fill text-info"></i> <?= htmlspecialchars($tituloFormulario) ?></h2>
      <p class="section-subtitle">Defina título, descrição e status para publicar a vaga com consistência.</p>
    </div>
    <a href="index.php?r=home" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>

  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show mb-4" role="alert" data-auto-close="true">
      <i class="<?= htmlspecialchars($alerta['icone']) ?> me-1"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-4 p-lg-5">
      <form method="POST" class="needs-validation" novalidate>
        <?= \App\Presentation\Support\Csrf::input() ?>

        <div class="mb-4">
          <label for="title" class="form-label">
            <i class="bi bi-file-earmark-text"></i> Título da vaga <span class="text-danger">*</span>
          </label>
          <input
            type="text"
            class="form-control form-control-lg"
            id="title"
            name="title"
            placeholder="Ex: Desenvolvedor(a) PHP Sênior"
            value="<?= htmlspecialchars($vacancy->title ?? '') ?>"
            required>
          <div class="invalid-feedback" role="alert">Informe o título da vaga.</div>
        </div>

        <div class="mb-4">
          <label for="description" class="form-label">
            <i class="bi bi-file-text"></i> Descrição da vaga <span class="text-danger">*</span>
          </label>
          <textarea
            class="form-control"
            id="description"
            name="description"
            rows="8"
            placeholder="Descreva atividades, responsabilidades, requisitos e benefícios..."
            required><?= htmlspecialchars($vacancy->description ?? '') ?></textarea>
          <div class="invalid-feedback" role="alert">Informe uma descrição para a vaga.</div>
          <small class="text-muted d-block mt-2">
            Quanto mais objetiva a descrição, melhor a qualidade das candidaturas.
          </small>
        </div>

        <div class="mb-4">
          <label class="form-label"><i class="bi bi-toggle-on"></i> Status da vaga</label>
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <input type="radio" class="btn-check" name="vacancy_status" id="active-yes" value="active" <?= $isActive ? 'checked' : '' ?>>
              <label class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center" for="active-yes">
                <span><i class="bi bi-check-circle-fill text-success me-1"></i> Ativa</span>
                <small class="text-muted">Visível para candidatos</small>
              </label>
            </div>
            <div class="col-12 col-md-6">
              <input type="radio" class="btn-check" name="vacancy_status" id="active-no" value="inactive" <?= !$isActive ? 'checked' : '' ?>>
              <label class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center" for="active-no">
                <span><i class="bi bi-pause-circle-fill text-danger me-1"></i> Inativa</span>
                <small class="text-muted">Oculta para candidatos</small>
              </label>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-4 mt-2 border-top border-secondary-subtle">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg"></i> Salvar vaga
          </button>
          <a href="index.php?r=home" class="btn btn-outline-secondary px-4">
            <i class="bi bi-x-lg"></i> Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="alert alert-info alert-dismissible fade show mt-4" role="alert" data-auto-close="true">
    <i class="bi bi-lightbulb-fill me-1"></i>
    Revise o texto antes de salvar para evitar vagas com descrição incompleta.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
  </div>
</section>

<?php
/**
 * @var \App\Application\DTOs\CompanyDto $company
 * @var array|null $alerta
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;
?>

<div class="jv-narrow">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow">Empresa</p>
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="jv-title mb-0"><?= htmlspecialchars($company->name) ?></h1>
        <span class="jv-badge jv-badge--info"><i class="bi bi-people me-1"></i><?= (int) $company->userCount ?> <?= $company->userCount === 1 ? 'recrutador vinculado' : 'recrutadores vinculados' ?></span>
      </div>
      <p class="jv-subtitle">Essas informações aparecem nos cartões das vagas publicadas pela sua empresa.</p>
    </div>
    <div class="jv-page-actions">
      <a href="index.php?r=home" class="btn btn-ghost">
        <i class="bi bi-grid-1x2"></i> Painel de vagas
      </a>
    </div>
  </header>

  <?php View::partial('alert', ['alerta' => $alerta]); ?>

  <form method="POST" class="jv-panel jv-panel-body needs-validation" novalidate>
    <?= Csrf::input() ?>

    <div class="jv-form-grid mb-4">
      <div>
        <label for="name" class="form-label">Nome da empresa</label>
        <input
          type="text"
          class="form-control"
          id="name"
          name="name"
          maxlength="150"
          value="<?= htmlspecialchars($company->name) ?>"
          required>
        <div class="invalid-feedback">Informe o nome da empresa.</div>
      </div>

      <div>
        <label for="sector" class="form-label">Setor de atuação</label>
        <input
          type="text"
          class="form-control"
          id="sector"
          name="sector"
          maxlength="100"
          placeholder="Ex.: SaaS & Cloud, Fintech, Consultoria"
          value="<?= htmlspecialchars($company->sector) ?>"
          required>
      </div>
    </div>

    <div class="mb-4">
      <label for="website" class="form-label">Site institucional (opcional)</label>
      <input
        type="url"
        class="form-control"
        id="website"
        name="website"
        maxlength="255"
        placeholder="https://suaempresa.com.br"
        value="<?= htmlspecialchars($company->website) ?>">
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Sobre a empresa</label>
      <textarea
        class="form-control"
        id="description"
        name="description"
        rows="5"
        maxlength="2000"
        placeholder="Descreva a cultura, área de atuação e missão da empresa..."><?= htmlspecialchars($company->description) ?></textarea>
    </div>

    <div class="jv-form-actions">
      <a href="index.php?r=home" class="btn btn-ghost">Voltar</a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> Salvar dados da empresa
      </button>
    </div>
  </form>
</div>

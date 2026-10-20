<?php
/**
 * @var object $vacancy  {title, description, isActive, area, seniority, workMode, topics, companyId, id?}
 * @var string $tituloPagina
 * @var array|null $alerta
 * @var array<string,string> $areas
 * @var array<string,string> $seniorities
 * @var array<string,string> $workModes
 * @var list<string> $popularTopics
 * @var bool $isAdmin
 * @var \App\Application\DTOs\CompanyDto[] $companies
 * @var \App\Application\DTOs\CompanyDto|null $myCompany
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$isActive = (bool) ($vacancy->isActive ?? true);
$isEdit = isset($vacancy->id);
$topics = is_array($vacancy->topics ?? null) ? $vacancy->topics : [];
$topicsCsv = implode(', ', $topics);
$topicMapLower = array_fill_keys(array_map('mb_strtolower', $topics), true);
?>

<div class="jv-narrow">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow"><a href="index.php?r=home" class="text-reset text-decoration-none">Vagas</a> / <?= $isEdit ? 'Editar' : 'Nova' ?></p>
      <h1 class="jv-title"><?= htmlspecialchars($tituloPagina) ?></h1>
      <p class="jv-subtitle">
        <?php if (!empty($myCompany)) : ?>
          Publicando em nome de <strong><?= htmlspecialchars($myCompany->name) ?></strong>. Defina área e tópicos para o match automático com candidatos.
        <?php else : ?>
          Defina título, descrição, área e tópicos técnicos para que o sistema calcule a compatibilidade dos candidatos.
        <?php endif; ?>
      </p>
    </div>
  </header>

  <?php View::partial('alert', ['alerta' => $alerta ?? null]); ?>

  <form method="POST" class="jv-panel jv-panel-body needs-validation" novalidate>
    <?= Csrf::input() ?>

    <?php if (!empty($isAdmin) && !empty($companies)) : ?>
      <div class="mb-4">
        <label for="company_id" class="form-label">Empresa responsável pela vaga</label>
        <select id="company_id" name="company_id" class="form-select">
          <option value="">Sem empresa vinculada</option>
          <?php foreach ($companies as $company) : ?>
            <option value="<?= htmlspecialchars($company->id) ?>" <?= ($vacancy->companyId ?? '') === $company->id ? 'selected' : '' ?>>
              <?= htmlspecialchars($company->name) ?> (<?= htmlspecialchars($company->sector) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="mb-4">
      <label for="title" class="form-label">Título da vaga</label>
      <input
        type="text"
        class="form-control form-control-lg"
        id="title"
        name="title"
        maxlength="255"
        placeholder="Ex.: Desenvolvedor(a) PHP Sênior"
        value="<?= htmlspecialchars($vacancy->title ?? '') ?>"
        required>
      <div class="invalid-feedback">Informe o título da vaga.</div>
    </div>

    <div class="jv-form-grid mb-4">
      <div>
        <label for="area" class="form-label">Área da vaga</label>
        <select id="area" name="area" class="form-select">
          <?php foreach ($areas as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= ($vacancy->area ?? 'backend') === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="seniority" class="form-label">Senioridade esperada</label>
        <select id="seniority" name="seniority" class="form-select">
          <?php foreach ($seniorities as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= ($vacancy->seniority ?? 'pleno') === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="work_mode" class="form-label">Modalidade</label>
        <select id="work_mode" name="work_mode" class="form-select">
          <?php foreach ($workModes as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= ($vacancy->workMode ?? 'remoto') === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-4" data-topic-picker>
      <label for="vacancy-topics-input" class="form-label">Tópicos e tecnologias da vaga</label>
      <input type="hidden" name="topics" value="<?= htmlspecialchars($topicsCsv) ?>" data-topic-hidden>

      <div class="jv-topic-box">
        <div class="jv-chips" data-topic-list>
          <?php foreach ($topics as $topic) : ?>
            <span class="jv-chip" data-topic-chip="<?= htmlspecialchars($topic) ?>">
              <?= htmlspecialchars($topic) ?>
              <button type="button" data-topic-remove="<?= htmlspecialchars($topic) ?>" aria-label="Remover <?= htmlspecialchars($topic) ?>">
                <i class="bi bi-x-lg"></i>
              </button>
            </span>
          <?php endforeach; ?>
        </div>
        <div class="jv-topic-entry">
          <input
            type="text"
            id="vacancy-topics-input"
            class="form-control form-control-sm"
            placeholder="Digite um tópico (ex.: PHP, MySQL, Docker) e pressione Enter ou vírgula"
            data-topic-input
            autocomplete="off">
          <button type="button" class="btn btn-ghost btn-sm" data-topic-add-btn>
            <i class="bi bi-plus-lg"></i> Adicionar
          </button>
        </div>
      </div>
      <div class="form-text mb-2">Os candidatos que tiverem esses tópicos no perfil ganharão maior % de match nesta vaga.</div>

      <div class="jv-topic-suggestions">
        <span class="text-muted small me-1">Sugestões rápidas:</span>
        <?php foreach ($popularTopics as $suggestion) : ?>
          <?php $isAdded = isset($topicMapLower[mb_strtolower($suggestion)]); ?>
          <button
            type="button"
            class="jv-suggestion-chip <?= $isAdded ? 'is-added' : '' ?>"
            data-topic-suggest="<?= htmlspecialchars($suggestion) ?>">
            <i class="bi <?= $isAdded ? 'bi-check2' : 'bi-plus' ?>"></i> <?= htmlspecialchars($suggestion) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="mb-4">
      <label for="description" class="form-label">Descrição da vaga</label>
      <textarea
        class="form-control"
        id="description"
        name="description"
        rows="6"
        placeholder="Atividades do dia a dia, responsabilidades, benefícios e diferenciais"
        aria-describedby="description-help"
        required><?= htmlspecialchars($vacancy->description ?? '') ?></textarea>
      <div id="description-help" class="form-text">Descrições objetivas atraem candidaturas mais alinhadas.</div>
      <div class="invalid-feedback">Informe a descrição da vaga.</div>
    </div>

    <fieldset>
      <legend class="form-label">Status</legend>
      <div class="jv-choice">
        <div>
          <input type="radio" class="btn-check" name="vacancy_status" id="status-active" value="active" <?= $isActive ? 'checked' : '' ?>>
          <label class="jv-choice-option" for="status-active">
            <span class="jv-status jv-status--active mt-1" aria-hidden="true"></span>
            <span><strong>Ativa</strong><small>Visível e aceitando candidaturas</small></span>
          </label>
        </div>
        <div>
          <input type="radio" class="btn-check" name="vacancy_status" id="status-inactive" value="inactive" <?= !$isActive ? 'checked' : '' ?>>
          <label class="jv-choice-option" for="status-inactive">
            <span class="jv-status jv-status--inactive mt-1" aria-hidden="true"></span>
            <span><strong>Inativa</strong><small>Oculta para candidatos</small></span>
          </label>
        </div>
      </div>
    </fieldset>

    <div class="jv-form-actions">
      <a href="index.php?r=home" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> <?= $isEdit ? 'Salvar alterações' : 'Publicar vaga' ?>
      </button>
    </div>
  </form>
</div>

<?php
/**
 * @var object $vacancy  {title, description, status, currentStatus, closeReasonLabel, positions, area, seniority, workMode, topics,
 *                        contractType, salaryMin, salaryMax, city, state, benefits, closesAt, companyId, id?}
 * @var string $tituloPagina
 * @var array|null $alerta
 * @var array<string,string> $areas
 * @var array<string,string> $seniorities
 * @var array<string,string> $workModes
 * @var list<string> $popularTopics
 * @var array<string,string> $contractTypes
 * @var array<string,string> $states
 * @var list<string> $popularBenefits
 * @var string $today  Y-m-d (Brasília)
 * @var bool $isAdmin
 * @var \App\Application\DTOs\CompanyDto[] $companies
 * @var \App\Application\DTOs\CompanyDto|null $myCompany
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$isEdit = isset($vacancy->id);
$selectedStatus = (string) ($vacancy->status ?? 'aberta');
$currentStatus = (string) ($vacancy->currentStatus ?? '');

// Only the moves allowed from the current status are offered (a published vacancy never goes back to draft).
$statusChoices = match ($currentStatus) {
  'rascunho' => [
    'rascunho' => ['Manter como rascunho', 'Só a sua empresa vê; ainda não recebe candidaturas', 'jv-status--draft'],
    'aberta' => ['Publicar agora', 'Fica visível e passa a aceitar candidaturas', 'jv-status--active'],
  ],
  'aberta' => [
    'aberta' => ['Aberta', 'Visível e aceitando candidaturas', 'jv-status--active'],
    'encerrada' => ['Encerrar', 'Some da lista de candidatos; o pipeline continua disponível', 'jv-status--inactive'],
  ],
  'encerrada' => [
    'encerrada' => ['Encerrada', (string) ($vacancy->closeReasonLabel ?? '') ?: 'Não recebe novas candidaturas', 'jv-status--inactive'],
    'aberta' => ['Reabrir', 'Volta a aceitar candidaturas', 'jv-status--active'],
  ],
  default => [
    'aberta' => ['Publicar agora', 'Visível e aceitando candidaturas', 'jv-status--active'],
    'rascunho' => ['Salvar como rascunho', 'Revise com calma e publique depois', 'jv-status--draft'],
  ],
};
$topics = is_array($vacancy->topics ?? null) ? $vacancy->topics : [];
$topicsCsv = implode(', ', $topics);
$topicMapLower = array_fill_keys(array_map('mb_strtolower', $topics), true);
$benefits = is_array($vacancy->benefits ?? null) ? $vacancy->benefits : [];
$benefitMapLower = array_fill_keys(array_map('mb_strtolower', $benefits), true);
$needsLocation = ($vacancy->workMode ?? 'remoto') !== 'remoto';
$closesAt = (string) ($vacancy->closesAt ?? '');
// An expired deadline being kept must not block the browser validation; new dates start today.
$closesAtMin = ($closesAt !== '' && $closesAt < $today) ? '' : $today;
$closesAtExpired = $closesAt !== '' && $closesAt < $today;
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
        <select id="work_mode" name="work_mode" class="form-select" data-work-mode>
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

    <fieldset class="jv-form-section">
      <legend>Contratação</legend>

      <div class="jv-form-grid mb-3">
        <div>
          <label for="contract_type" class="form-label">Tipo de contrato</label>
          <select id="contract_type" name="contract_type" class="form-select">
            <?php foreach ($contractTypes as $value => $label) : ?>
              <option value="<?= htmlspecialchars($value) ?>" <?= ($vacancy->contractType ?? 'clt') === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="salary_min" class="form-label">Salário de <span class="text-muted fw-normal">(R$/mês)</span></label>
          <input type="text" inputmode="numeric" class="form-control jv-mono" id="salary_min" name="salary_min" placeholder="Ex.: 6.000"
            value="<?= htmlspecialchars((string) ($vacancy->salaryMin ?? '')) ?>" aria-describedby="salary-help" autocomplete="off">
        </div>

        <div>
          <label for="salary_max" class="form-label">Salário até <span class="text-muted fw-normal">(R$/mês)</span></label>
          <input type="text" inputmode="numeric" class="form-control jv-mono" id="salary_max" name="salary_max" placeholder="Ex.: 9.000"
            value="<?= htmlspecialchars((string) ($vacancy->salaryMax ?? '')) ?>" aria-describedby="salary-help" autocomplete="off">
        </div>
      </div>
      <div id="salary-help" class="form-text mb-4">Deixe os dois em branco para mostrar <strong>A combinar</strong>. Pode informar só um dos lados (“a partir de” ou “até”).</div>

      <div class="jv-form-grid jv-form-grid--location mb-1" data-location-fields>
        <div>
          <label for="city" class="form-label">Cidade <span class="text-muted fw-normal" data-location-optional<?= $needsLocation ? ' hidden' : '' ?>>(opcional)</span></label>
          <input type="text" class="form-control" id="city" name="city" maxlength="100" placeholder="Ex.: Curitiba"
            value="<?= htmlspecialchars((string) ($vacancy->city ?? '')) ?>" <?= $needsLocation ? 'required' : '' ?> aria-describedby="location-help">
          <div class="invalid-feedback">Informe a cidade.</div>
        </div>

        <div>
          <label for="state" class="form-label">UF</label>
          <select id="state" name="state" class="form-select" <?= $needsLocation ? 'required' : '' ?> aria-describedby="location-help">
            <option value="">—</option>
            <?php foreach ($states as $value => $label) : ?>
              <option value="<?= htmlspecialchars($value) ?>" <?= ($vacancy->state ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Selecione a UF.</div>
        </div>
      </div>
      <div id="location-help" class="form-text mb-4" data-location-hint>
        <?= $needsLocation
          ? 'Obrigatório para vagas presenciais e híbridas.'
          : 'Em vagas remotas, informe se quiser indicar a sede do time.' ?>
      </div>

      <div class="mb-4" data-topic-picker data-topic-empty="Nenhum benefício adicionado.">
        <label for="vacancy-benefits-input" class="form-label">Benefícios</label>
        <input type="hidden" name="benefits" value="<?= htmlspecialchars(implode(', ', $benefits)) ?>" data-topic-hidden>

        <div class="jv-topic-box">
          <div class="jv-chips" data-topic-list>
            <?php foreach ($benefits as $benefit) : ?>
              <span class="jv-chip"><?= htmlspecialchars($benefit) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="jv-topic-entry">
            <input type="text" id="vacancy-benefits-input" class="form-control form-control-sm"
              placeholder="Digite um benefício (ex.: Plano de saúde) e pressione Enter ou vírgula" data-topic-input autocomplete="off">
            <button type="button" class="btn btn-ghost btn-sm" data-topic-add-btn>
              <i class="bi bi-plus-lg"></i> Adicionar
            </button>
          </div>
        </div>

        <div class="jv-topic-suggestions mt-2">
          <span class="text-muted small me-1">Comuns:</span>
          <?php foreach ($popularBenefits as $suggestion) : ?>
            <?php $isAdded = isset($benefitMapLower[mb_strtolower($suggestion)]); ?>
            <button type="button" class="jv-suggestion-chip <?= $isAdded ? 'is-added' : '' ?>" data-topic-suggest="<?= htmlspecialchars($suggestion) ?>">
              <i class="bi <?= $isAdded ? 'bi-check2' : 'bi-plus' ?>"></i> <?= htmlspecialchars($suggestion) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </fieldset>

    <fieldset class="jv-form-section">
      <legend>Inscrições</legend>

      <div class="mb-4">
        <label for="positions" class="form-label">Número de posições</label>
        <input
          type="number"
          class="form-control jv-input-narrow"
          id="positions"
          name="positions"
          min="1"
          max="999"
          step="1"
          value="<?= (int) ($vacancy->positions ?? 1) ?>"
          aria-describedby="positions-help"
          required>
        <div id="positions-help" class="form-text">Quantas pessoas serão contratadas. Ao aprovar a última no pipeline, a vaga é encerrada automaticamente.</div>
        <div class="invalid-feedback">Informe entre 1 e 999 posições.</div>
      </div>

      <div class="mb-4">
        <label for="closes_at" class="form-label">Prazo de inscrição <span class="text-muted fw-normal">(opcional)</span></label>
        <input type="date" class="form-control jv-input-date" id="closes_at" name="closes_at"
          value="<?= htmlspecialchars($closesAt) ?>" <?= $closesAtMin !== '' ? 'min="' . htmlspecialchars($closesAtMin) . '"' : '' ?> aria-describedby="closes-at-help">
        <div id="closes-at-help" class="form-text">
          <?php if ($closesAtExpired) : ?>
            <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Esse prazo já passou. Para reabrir a vaga, escolha uma nova data ou apague o campo.</span>
          <?php else : ?>
            Último dia para se candidatar (até 23:59, horário de Brasília). Depois disso a vaga é encerrada automaticamente.
          <?php endif; ?>
        </div>
        <div class="invalid-feedback">O prazo não pode estar no passado.</div>
      </div>

      <fieldset class="mt-2">
        <legend class="form-label">Status</legend>
        <div class="jv-choice">
          <?php foreach ($statusChoices as $value => [$label, $hint, $dotClass]) : ?>
            <div>
              <input type="radio" class="btn-check" name="vacancy_status" id="status-<?= htmlspecialchars($value) ?>" value="<?= htmlspecialchars($value) ?>" <?= $selectedStatus === $value ? 'checked' : '' ?>>
              <label class="jv-choice-option" for="status-<?= htmlspecialchars($value) ?>">
                <span class="jv-status <?= htmlspecialchars($dotClass) ?> mt-1" aria-hidden="true"></span>
                <span><strong><?= htmlspecialchars($label) ?></strong><small><?= htmlspecialchars($hint) ?></small></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>
    </fieldset>

    <div class="jv-form-actions">
      <a href="index.php?r=home" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> <?= $isEdit ? 'Salvar alterações' : 'Salvar vaga' ?>
      </button>
    </div>
  </form>
</div>

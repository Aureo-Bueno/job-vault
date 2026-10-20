<?php
/**
 * @var array|null $alerta
 * @var \App\Application\DTOs\CandidateProfileDto $profile
 * @var \App\Application\DTOs\VacancyDto[] $vacancies
 * @var array<string,true> $appliedMap
 * @var array<string,string> $areas
 * @var array<string,string> $workModes
 * @var string $search
 * @var string $areaFilter
 * @var string $workModeFilter
 * @var array<string,string> $contractTypes
 * @var array<string,string> $states
 * @var list<int> $salaryOptions
 * @var int $salaryFilter
 * @var string $stateFilter
 * @var string $contractFilter
 * @var string $today  Y-m-d (Brasília)
 * @var string $sort
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$total = count($vacancies);
$appliedCount = count(array_filter($vacancies, static fn ($v): bool => isset($appliedMap[$v->id])));
$hasFilters = $search !== '' || $areaFilter !== '' || $workModeFilter !== ''
  || $salaryFilter > 0 || $stateFilter !== '' || $contractFilter !== '';
$benefitsShown = 3;
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow">Candidaturas &amp; Match</p>
    <h1 class="jv-title">Vagas abertas</h1>
    <p class="jv-subtitle" id="apply-summary" data-live-region aria-live="polite">
      <?= $total === 1 ? '1 vaga encontrada' : $total . ' vagas encontradas' ?>
      <?php if ($search !== '' && $sort === 'relevance' && $total > 1) : ?>
        · mais relevantes primeiro
      <?php endif; ?>
      <?php if ($appliedCount > 0) : ?>
        · você já se candidatou a <?= $appliedCount ?>
      <?php endif; ?>
    </p>
  </div>
  <div class="jv-page-actions">
    <a href="index.php?r=profile" class="btn <?= $profile->isComplete ? 'btn-ghost' : 'btn-primary' ?>">
      <i class="bi bi-person-badge"></i>
      <?= $profile->isComplete ? 'Editar meu perfil' : 'Completar perfil para Match' ?>
    </a>
  </div>
</header>

<?php View::partial('alert', ['alerta' => $alerta]); ?>

<?php if (!$profile->isComplete) : ?>
  <section class="jv-panel jv-banner-profile mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <span class="jv-brand-mark"><i class="bi bi-stars"></i></span>
        <div>
          <strong class="d-block text-light">Ative o cálculo de compatibilidade (Match)</strong>
          <span class="text-muted small">Descreva seu perfil, escolha sua área de atuação e adicione suas tecnologias para ver o % de match em cada vaga.</span>
        </div>
      </div>
      <a href="index.php?r=profile" class="btn btn-primary btn-sm">
        <i class="bi bi-sliders"></i> Preencher meu perfil
      </a>
    </div>
  </section>
<?php endif; ?>

<section class="jv-panel mb-3">
  <form method="GET" class="jv-toolbar border-bottom-0" role="search" data-live-search>
    <input type="hidden" name="r" value="vacancies/apply">
    <label class="jv-search mb-0">
      <span class="visually-hidden">Buscar em cargo, descrição, tecnologias e benefícios</span>
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="search" class="form-control" placeholder="Buscar em cargo, descrição, tecnologias e benefícios" value="<?= htmlspecialchars($search) ?>" autocomplete="off" data-live-search-input>
    </label>
    <label class="visually-hidden" for="apply-area-filter">Área</label>
    <select id="apply-area-filter" name="area" class="form-select">
      <option value="">Todas as áreas</option>
      <?php foreach ($areas as $val => $label) : ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= $areaFilter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="apply-mode-filter">Modalidade</label>
    <select id="apply-mode-filter" name="work_mode" class="form-select">
      <option value="">Todas as modalidades</option>
      <?php foreach ($workModes as $val => $label) : ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= $workModeFilter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="apply-contract-filter">Contrato</label>
    <select id="apply-contract-filter" name="contract_type" class="form-select">
      <option value="">Qualquer contrato</option>
      <?php foreach ($contractTypes as $val => $label) : ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= $contractFilter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="apply-state-filter">UF</label>
    <select id="apply-state-filter" name="state" class="form-select jv-select-uf">
      <option value="">Todas as UFs</option>
      <?php foreach ($states as $val => $label) : ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= $stateFilter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="apply-salary-filter">Salário mínimo</label>
    <select id="apply-salary-filter" name="min_salary" class="form-select">
      <option value="">Qualquer salário</option>
      <?php foreach ($salaryOptions as $amount) : ?>
        <option value="<?= $amount ?>" <?= $salaryFilter === $amount ? 'selected' : '' ?>>A partir de R$ <?= number_format($amount, 0, ',', '.') ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="apply-sort">Ordenar por</label>
    <select id="apply-sort" name="sort" class="form-select">
      <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?> title="Com busca: os resultados que mais combinam com o texto. Sem busca: maior match.">Mais relevantes</option>
      <option value="match" <?= $sort === 'match' ? 'selected' : '' ?>>Maior match para mim</option>
      <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Mais recentes</option>
    </select>
    <button type="submit" class="btn btn-ghost"><i class="bi bi-funnel"></i> Filtrar</button>
    <span id="apply-clear" data-live-region>
      <?php if ($hasFilters) : ?>
        <a href="index.php?r=vacancies/apply" class="btn btn-link text-muted px-2">Limpar</a>
      <?php endif; ?>
    </span>
  </form>
</section>

<div id="apply-results" data-live-region>
<?php if ($total === 0) : ?>
  <div class="jv-panel jv-empty">
    <i class="bi bi-inbox" aria-hidden="true"></i>
    <strong><?= $hasFilters ? 'Nenhuma vaga encontrada com esses filtros' : 'Nenhuma vaga aberta no momento' ?></strong>
    <?= $hasFilters
      ? ($salaryFilter > 0
        ? 'O filtro de salário ignora vagas “A combinar”. Experimente um valor menor ou limpar os filtros.'
        : 'Experimente limpar os filtros ou buscar por outro tópico.')
      : 'Novas vagas aparecem aqui assim que forem publicadas.' ?>
  </div>
<?php else : ?>
  <div class="jv-card-grid">
    <?php foreach ($vacancies as $vacancy) : ?>
      <?php
      $alreadyApplied = isset($appliedMap[$vacancy->id]);
      $published = $vacancy->createdAt !== '' ? date('d/m/Y', strtotime($vacancy->createdAt)) : '—';
      $matchedLookup = array_fill_keys($vacancy->matchedTopics, true);
      $daysLeft = $vacancy->hasDeadline() ? (int) round((strtotime($vacancy->closesAt) - strtotime($today)) / 86400) : null;
      $extraBenefits = max(0, count($vacancy->benefits) - $benefitsShown);
      ?>
      <article class="jv-panel jv-job jv-rail-card jv-rail--active">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div>
            <?php if ($vacancy->companyName !== '') : ?>
              <span class="jv-job-company">
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($vacancy->companyName) ?>
                <?php if ($vacancy->companySector !== '') : ?>
                  <span class="text-muted">· <?= htmlspecialchars($vacancy->companySector) ?></span>
                <?php endif; ?>
              </span>
            <?php endif; ?>
            <h2 class="mt-1"><?= htmlspecialchars($vacancy->title) ?></h2>
          </div>

          <?php if ($vacancy->matchScore !== null && $profile->isComplete) : ?>
            <span class="jv-match jv-match--<?= htmlspecialchars($vacancy->matchLevel) ?>" title="Sua compatibilidade com esta vaga">
              <i class="bi bi-stars" aria-hidden="true"></i>
              <?= (int) $vacancy->matchScore ?>% match
            </span>
          <?php endif; ?>
        </div>

        <div class="jv-job-terms">
          <span class="jv-salary <?= $vacancy->isSalaryNegotiable() ? 'jv-salary--negotiable' : '' ?>">
            <?= htmlspecialchars($vacancy->salaryLabel) ?><?php if (!$vacancy->isSalaryNegotiable()) : ?><small>/mês</small><?php endif; ?>
          </span>
          <span class="jv-badge"><?= htmlspecialchars($vacancy->contractTypeLabel) ?></span>
        </div>

        <div class="d-flex flex-wrap gap-1">
          <span class="jv-badge jv-badge--info"><?= htmlspecialchars($vacancy->areaLabel) ?></span>
          <span class="jv-badge"><?= htmlspecialchars($vacancy->seniorityLabel) ?></span>
          <span class="jv-badge">
            <i class="bi <?= $vacancy->workMode === 'remoto' ? 'bi-globe2' : 'bi-geo-alt' ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($vacancy->workModeLabel) ?><?php if ($vacancy->locationLabel !== '') : ?> · <?= htmlspecialchars($vacancy->locationLabel) ?><?php endif; ?>
          </span>
        </div>

        <p class="jv-clamp mb-0"><?= htmlspecialchars($vacancy->description) ?></p>

        <?php if (!empty($vacancy->topics)) : ?>
          <div class="jv-chips">
            <?php foreach ($vacancy->topics as $topic) : ?>
              <?php $isMatched = isset($matchedLookup[$topic]); ?>
              <span
                class="jv-chip jv-chip--static <?= $isMatched ? 'jv-topic--match' : '' ?>"
                title="<?= $isMatched ? 'Compatível com seu perfil!' : 'Requisito da vaga' ?>">
                <?php if ($isMatched) : ?><i class="bi bi-check2"></i><?php endif; ?>
                <?= htmlspecialchars($topic) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($vacancy->benefits !== []) : ?>
          <p class="jv-job-benefits mb-0" title="<?= htmlspecialchars(implode(' · ', $vacancy->benefits)) ?>">
            <i class="bi bi-gift" aria-hidden="true"></i>
            <span class="visually-hidden">Benefícios:</span>
            <?= htmlspecialchars(implode(' · ', array_slice($vacancy->benefits, 0, $benefitsShown))) ?>
            <?php if ($extraBenefits > 0) : ?><span class="text-muted">+<?= $extraBenefits ?></span><?php endif; ?>
          </p>
        <?php endif; ?>

        <div class="jv-job-foot">
          <?php if ($daysLeft !== null) : ?>
            <span class="jv-mono jv-deadline <?= $daysLeft <= 3 ? 'jv-deadline--soon' : '' ?>" title="Prazo de inscrição: <?= htmlspecialchars($vacancy->closesAtLabel) ?>">
              <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i><?= match (true) {
                $daysLeft <= 0 => 'Encerra hoje',
                $daysLeft === 1 => 'Encerra amanhã',
                $daysLeft <= 3 => 'Encerra em ' . $daysLeft . ' dias',
                default => 'Até ' . htmlspecialchars(substr($vacancy->closesAtLabel, 0, 5)),
              } ?>
            </span>
          <?php else : ?>
            <span class="jv-mono text-muted" title="Publicada em"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= htmlspecialchars($published) ?></span>
          <?php endif; ?>
          <?php if ($alreadyApplied) : ?>
            <a href="index.php?r=applications" class="jv-badge jv-badge--success text-decoration-none" title="Acompanhar em Minhas candidaturas">
              <i class="bi bi-check2"></i> Candidatura enviada · ver andamento
            </a>
          <?php else : ?>
            <form method="POST" class="m-0">
              <?= Csrf::input() ?>
              <input type="hidden" name="vacancy_id" value="<?= htmlspecialchars($vacancy->id) ?>">
              <button type="submit" class="btn btn-success btn-sm" aria-label="Candidatar-se a <?= htmlspecialchars($vacancy->title) ?>">
                <i class="bi bi-send"></i> Candidatar-se
              </button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>

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
 * @var string $sort
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$total = count($vacancies);
$appliedCount = count(array_filter($vacancies, static fn ($v): bool => isset($appliedMap[$v->id])));
$hasFilters = $search !== '' || $areaFilter !== '' || $workModeFilter !== '';
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow">Candidaturas &amp; Match</p>
    <h1 class="jv-title">Vagas abertas</h1>
    <p class="jv-subtitle">
      <?= $total === 1 ? '1 vaga encontrada' : $total . ' vagas encontradas' ?>
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
  <form method="GET" class="jv-toolbar border-bottom-0" role="search">
    <input type="hidden" name="r" value="vacancies/apply">
    <label class="jv-search mb-0">
      <span class="visually-hidden">Buscar por cargo ou tecnologia</span>
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="search" class="form-control" placeholder="Buscar por cargo ou tópico (ex.: PHP, React, SQL)" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
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
    <label class="visually-hidden" for="apply-sort">Ordenar por</label>
    <select id="apply-sort" name="sort" class="form-select">
      <option value="match" <?= $sort === 'match' ? 'selected' : '' ?>>Maior match para mim</option>
      <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Mais recentes</option>
    </select>
    <button type="submit" class="btn btn-ghost"><i class="bi bi-funnel"></i> Filtrar</button>
    <?php if ($hasFilters) : ?>
      <a href="index.php?r=vacancies/apply" class="btn btn-link text-muted px-2">Limpar</a>
    <?php endif; ?>
  </form>
</section>

<?php if ($total === 0) : ?>
  <div class="jv-panel jv-empty">
    <i class="bi bi-inbox" aria-hidden="true"></i>
    <strong><?= $hasFilters ? 'Nenhuma vaga encontrada com esses filtros' : 'Nenhuma vaga aberta no momento' ?></strong>
    <?= $hasFilters ? 'Experimente limpar os filtros ou buscar por outro tópico.' : 'Novas vagas aparecem aqui assim que forem publicadas.' ?>
  </div>
<?php else : ?>
  <div class="jv-card-grid">
    <?php foreach ($vacancies as $vacancy) : ?>
      <?php
      $alreadyApplied = isset($appliedMap[$vacancy->id]);
      $published = $vacancy->createdAt !== '' ? date('d/m/Y', strtotime($vacancy->createdAt)) : '—';
      $matchedLookup = array_fill_keys($vacancy->matchedTopics, true);
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

        <div class="d-flex flex-wrap gap-1">
          <span class="jv-badge jv-badge--info"><?= htmlspecialchars($vacancy->areaLabel) ?></span>
          <span class="jv-badge"><?= htmlspecialchars($vacancy->seniorityLabel) ?></span>
          <span class="jv-badge"><?= htmlspecialchars($vacancy->workModeLabel) ?></span>
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

        <div class="jv-job-foot">
          <span class="jv-mono text-muted" title="Publicada em"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= htmlspecialchars($published) ?></span>
          <?php if ($alreadyApplied) : ?>
            <span class="jv-badge jv-badge--success"><i class="bi bi-check2"></i> Candidatura enviada</span>
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

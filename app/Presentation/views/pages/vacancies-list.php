<?php
/**
 * @var array|null $alerta
 * @var \App\Application\DTOs\VacancyDto[] $vacancies
 * @var bool $canEdit
 * @var bool $canDelete
 * @var bool $canCreate
 * @var bool $isAdmin
 * @var \App\Application\DTOs\CompanyDto|null $myCompany
 * @var array<string,string> $areas
 * @var string $areaFilter
 * @var array $pagination
 * @var string $search
 * @var string $statusFilter
 * @var string $queryString
 * @var int $total
 */
use App\Presentation\View;

$hasFilters = $search !== '' || $statusFilter !== '' || ($areaFilter ?? '') !== '';
$summary = $total === 1 ? '1 vaga' : $total . ' vagas';
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow"><?= !empty($myCompany) ? htmlspecialchars($myCompany->name) : 'Vagas' ?></p>
    <h1 class="jv-title">Painel de vagas</h1>
    <p class="jv-subtitle">
      <?= htmlspecialchars($hasFilters ? $summary . ' encontradas com os filtros atuais' : $summary . ' cadastradas') ?>
      <?php if (!empty($myCompany)) : ?>
        · vinculadas à sua empresa
      <?php endif; ?>
    </p>
  </div>
  <?php if ($canCreate) : ?>
    <div class="jv-page-actions">
      <a href="index.php?r=vacancies/new" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova vaga
      </a>
    </div>
  <?php endif; ?>
</header>

<?php View::partial('alert', ['alerta' => $alerta]); ?>

<section class="jv-panel">
  <form method="GET" class="jv-toolbar" role="search">
    <input type="hidden" name="r" value="vacancies">
    <label class="jv-search mb-0">
      <span class="visually-hidden">Buscar por título ou tópico</span>
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="search" class="form-control" placeholder="Buscar por título ou tópico (ex.: PHP, React)" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
    </label>
    <label class="visually-hidden" for="area_filter">Área</label>
    <select name="area_filter" id="area_filter" class="form-select">
      <option value="">Todas as áreas</option>
      <?php foreach ($areas as $val => $label) : ?>
        <option value="<?= htmlspecialchars($val) ?>" <?= ($areaFilter ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="status_filter">Status</label>
    <select name="status_filter" id="status_filter" class="form-select">
      <option value="">Todos os status</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Ativas</option>
      <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inativas</option>
    </select>
    <button type="submit" class="btn btn-ghost"><i class="bi bi-funnel"></i> Filtrar</button>
    <?php if ($hasFilters) : ?>
      <a href="index.php?r=vacancies" class="btn btn-link text-muted px-2">Limpar</a>
    <?php endif; ?>
  </form>

  <?php if (empty($vacancies)) : ?>
    <div class="jv-empty">
      <i class="bi bi-briefcase" aria-hidden="true"></i>
      <strong><?= $hasFilters ? 'Nenhuma vaga com esses filtros' : 'Nenhuma vaga cadastrada' ?></strong>
      <?= $hasFilters ? 'Ajuste a busca ou limpe os filtros.' : ($canCreate ? 'Publique a primeira vaga para começar a receber candidaturas.' : '') ?>
    </div>
  <?php else : ?>
    <div class="table-responsive">
      <table class="table jv-table jv-table-stack table-hover">
        <thead>
          <tr>
            <th scope="col">Vaga &amp; Tópicos</th>
            <th scope="col">Perfil</th>
            <th scope="col">Candidatos</th>
            <th scope="col">Status</th>
            <th scope="col">Publicada em</th>
            <?php if ($canEdit || $canDelete) : ?>
              <th scope="col" class="text-end"><span class="visually-hidden">Ações</span></th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vacancies as $vacancy) : ?>
            <?php
            $vacancyId = urlencode($vacancy->id);
            $published = $vacancy->createdAt !== '' ? date('d/m/Y', strtotime($vacancy->createdAt)) : '—';
            ?>
            <tr class="jv-rail <?= $vacancy->isActive ? 'jv-rail--active' : 'jv-rail--inactive' ?>">
              <td data-label="">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <span class="jv-cell-title mb-0"><?= htmlspecialchars($vacancy->title) ?></span>
                  <?php if ($vacancy->companyName !== '') : ?>
                    <span class="jv-badge jv-badge--info"><i class="bi bi-building me-1"></i><?= htmlspecialchars($vacancy->companyName) ?></span>
                  <?php endif; ?>
                </div>
                <span class="jv-cell-sub jv-clamp mb-2"><?= htmlspecialchars($vacancy->description) ?></span>
                <?php if (!empty($vacancy->topics)) : ?>
                  <div class="jv-chips">
                    <?php foreach ($vacancy->topics as $topic) : ?>
                      <span class="jv-chip jv-chip--static"><?= htmlspecialchars($topic) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Perfil" class="text-nowrap">
                <div class="d-flex flex-column gap-1 align-items-start">
                  <span class="jv-badge"><?= htmlspecialchars($vacancy->areaLabel) ?></span>
                  <span class="text-muted small"><?= htmlspecialchars($vacancy->seniorityLabel) ?> · <?= htmlspecialchars($vacancy->workModeLabel) ?></span>
                </div>
              </td>
              <td data-label="Candidatos" class="text-nowrap">
                <a href="index.php?r=vacancies/applicants&amp;id=<?= $vacancyId ?>" class="btn btn-ghost btn-sm">
                  <i class="bi bi-people"></i>
                  <?= (int) $vacancy->applicantCount ?> <?= $vacancy->applicantCount === 1 ? 'inscrito' : 'inscritos' ?>
                </a>
              </td>
              <td data-label="Status">
                <span class="jv-status <?= $vacancy->isActive ? 'jv-status--active' : 'jv-status--inactive' ?>">
                  <?= $vacancy->isActive ? 'Ativa' : 'Inativa' ?>
                </span>
              </td>
              <td data-label="Publicada em" class="text-nowrap"><span class="jv-mono text-muted"><?= htmlspecialchars($published) ?></span></td>
              <?php if ($canEdit || $canDelete) : ?>
                <td data-label="">
                  <div class="jv-actions">
                    <?php if ($canEdit) : ?>
                      <a href="index.php?r=vacancies/edit&amp;id=<?= $vacancyId ?>" class="jv-icon-btn" title="Editar" aria-label="Editar <?= htmlspecialchars($vacancy->title) ?>">
                        <i class="bi bi-pencil"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canDelete) : ?>
                      <a href="index.php?r=vacancies/delete&amp;id=<?= $vacancyId ?>" class="jv-icon-btn jv-icon-btn--danger" title="Excluir" aria-label="Excluir <?= htmlspecialchars($vacancy->title) ?>">
                        <i class="bi bi-trash3"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php View::partial('pagination', [
      'pagination' => $pagination,
      'baseUrl' => 'index.php?r=vacancies',
      'queryString' => $queryString,
      'summary' => $summary,
    ]); ?>
  <?php endif; ?>
</section>

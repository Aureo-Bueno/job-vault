<?php
// Expecting: $alerta, $vacancies, $canEdit, $canDelete, $canCreate, $pagination, $search, $statusFilter, $queryString
?>

<section class="container-content page-section p-3 p-lg-4">
  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show mb-4" role="alert" data-auto-close="true">
      <i class="<?= htmlspecialchars($alerta['icone']) ?> me-1"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-briefcase-fill text-info"></i> Vagas de emprego</h2>
      <p class="section-subtitle">Busque, filtre e gerencie vagas com visão clara por status.</p>
    </div>
    <?php if ($canCreate) : ?>
      <a href="index.php?r=vacancies/new" class="btn btn-primary">
        <i class="bi bi-plus-circle-fill"></i> Nova vaga
      </a>
    <?php endif; ?>
  </div>

  <div class="card toolbar-card">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-lg-5">
          <label for="search" class="form-label"><i class="bi bi-search"></i> Buscar por título</label>
          <input
            type="text"
            name="search"
            id="search"
            class="form-control"
            placeholder="Ex: Desenvolvedor PHP"
            value="<?= htmlspecialchars($search) ?>"
            autocomplete="off">
        </div>

        <div class="col-12 col-lg-4">
          <label for="status_filter" class="form-label"><i class="bi bi-funnel-fill"></i> Status</label>
          <select name="status_filter" id="status_filter" class="form-select">
            <option value="">Todos</option>
            <option value="s" <?= $statusFilter === 's' ? 'selected' : '' ?>>Ativo</option>
            <option value="n" <?= $statusFilter === 'n' ? 'selected' : '' ?>>Inativo</option>
          </select>
        </div>

        <div class="col-12 col-lg-3 d-grid">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-funnel"></i> Aplicar filtro
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Título</th>
            <th>Descrição</th>
            <th>Status</th>
            <th>Criada em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vacancies)) : ?>
            <tr>
              <td colspan="6" class="empty-state">
                <i class="bi bi-inbox"></i> Nenhuma vaga encontrada com os filtros atuais.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($vacancies as $index => $vacancy) : ?>
              <?php
              $statusAtivo = ($vacancy->isActive ?? 'n') === 's';
              $badgeClass = $statusAtivo ? 'bg-success' : 'bg-secondary';
              $statusText = $statusAtivo ? 'Ativo' : 'Inativo';
              $descricaoCurta = trim((string) ($vacancy->description ?? ''));
              $descricaoPreview = strlen($descricaoCurta) > 88 ? substr($descricaoCurta, 0, 88) . '...' : $descricaoCurta;
              $dataFormatada = !empty($vacancy->createdAt) ? date('d/m/Y H:i', strtotime($vacancy->createdAt)) : '-';
              $vacancyId = (string) ($vacancy->id ?? '');
              ?>
              <tr>
                <td><strong><?= $index + 1 ?></strong></td>
                <td><strong><?= htmlspecialchars($vacancy->title ?? '') ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($descricaoPreview) ?></td>
                <td><span class="badge status-badge <?= $badgeClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                <td class="small text-nowrap"><?= htmlspecialchars($dataFormatada) ?></td>
                <td>
                  <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <?php if ($canEdit) : ?>
                      <a href="index.php?r=vacancies/edit&id=<?= htmlspecialchars($vacancyId) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-fill"></i> Editar
                      </a>
                    <?php endif; ?>

                    <?php if ($canDelete) : ?>
                      <a href="index.php?r=vacancies/delete&id=<?= htmlspecialchars($vacancyId) ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill"></i> Excluir
                      </a>
                    <?php endif; ?>

                    <?php if (!$canEdit && !$canDelete) : ?>
                      <span class="badge status-badge bg-secondary">Sem permissões</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($pagination)) : ?>
    <nav class="d-flex justify-content-center mt-4 app-pagination" aria-label="Paginação de vagas">
      <div class="d-flex gap-2 flex-wrap justify-content-center">
        <?php foreach ($pagination as $pagina) : ?>
          <?php $class = $pagina['atual'] ? 'btn-primary' : 'btn-outline-secondary'; ?>
          <a href="index.php?r=vacancies&pagina=<?= $pagina['pagina'] ?><?= $queryString ? '&' . $queryString : '' ?>" class="btn <?= $class ?>">
            <?= $pagina['pagina'] ?>
          </a>
        <?php endforeach; ?>
      </div>
    </nav>
  <?php endif; ?>
</section>

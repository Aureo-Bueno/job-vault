<?php
// Expecting: $alerta, $vagas, $podeEditar, $podeDeletar, $podeCriar, $paginacao, $busca, $filtroStatus, $queryString
?>

<div class="container-content p-5">
  <!-- Alerts -->
  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show" role="alert">
      <i class="<?= htmlspecialchars($alerta['icone']) ?>"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Header Section -->
  <div class="row mb-4 align-items-center">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-briefcase-fill text-primary"></i> Vagas de Emprego
      </h2>
      <p class="text-muted small mb-0">Gerencie as vagas do seu sistema</p>
    </div>
    <?php if ($podeCriar) : ?>
      <div class="col-auto">
        <a href="index.php?r=vagas/novo">
          <button class="btn btn-primary fw-bold">
            <i class="bi bi-plus-circle-fill"></i> Nova Vaga
          </button>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Filter Section -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-5">
          <label for="busca" class="form-label text-white">
            <i class="bi bi-search"></i> Buscar por Título
          </label>
          <input type="text" name="busca" id="busca" class="form-control" placeholder="Digite o título..." value="<?= htmlspecialchars($busca) ?>">
        </div>

        <div class="col-md-4">
          <label for="filtroStatus" class="form-label text-white">
            <i class="bi bi-funnel-fill"></i> Status
          </label>
          <select name="filtroStatus" id="filtroStatus" class="form-select">
            <option value="">Todos</option>
            <option value="s" <?= $filtroStatus == 's' ? 'selected' : '' ?>>Ativo</option>
            <option value="n" <?= $filtroStatus == 'n' ? 'selected' : '' ?>>Inativo</option>
          </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="bi bi-search"></i> Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Table Section -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="border-bottom">
          <tr>
            <th class="text-white fw-bold"><i class="bi bi-hash"></i> ID</th>
            <th class="text-white fw-bold"><i class="bi bi-file-earmark-text"></i> Título</th>
            <th class="text-white fw-bold"><i class="bi bi-file-text"></i> Descrição</th>
            <th class="text-white fw-bold"><i class="bi bi-tag-fill"></i> Status</th>
            <th class="text-white fw-bold"><i class="bi bi-calendar-event"></i> Data</th>
            <th class="text-white fw-bold"><i class="bi bi-gear-fill"></i> Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vagas)) : ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-inbox"></i> Nenhuma vaga encontrada no sistema!
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($vagas as $vaga) : ?>
              <?php
              $statusAtivo = ($vaga->ativo ?? 'n') === 's';
              $badgeClass = $statusAtivo ? 'bg-success' : 'bg-secondary';
              $statusText = $statusAtivo ? 'Ativo' : 'Inativo';
              $descricaoCurta = substr($vaga->descricao ?? '', 0, 50);
              $dataFormatada = !empty($vaga->data) ? date('d/m/Y H:i', strtotime($vaga->data)) : '-';
              ?>
              <tr class="align-middle">
                <td><strong>#<?= (int) $vaga->id ?></strong></td>
                <td><strong><?= htmlspecialchars($vaga->titulo ?? '') ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($descricaoCurta) ?>...</td>
                <td><span class="badge <?= $badgeClass ?>"><?= $statusText ?></span></td>
                <td class="small"><?= $dataFormatada ?></td>
                <td>
                  <?php if ($podeEditar) : ?>
                    <a href="index.php?r=vagas/editar&id=<?= (int) $vaga->id ?>" class="me-2">
                      <button type="button" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-fill"></i> Editar
                      </button>
                    </a>
                  <?php endif; ?>

                  <?php if ($podeDeletar) : ?>
                    <a href="index.php?r=vagas/excluir&id=<?= (int) $vaga->id ?>">
                      <button type="button" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill"></i> Excluir
                      </button>
                    </a>
                  <?php endif; ?>

                  <?php if (!$podeEditar && !$podeDeletar) : ?>
                    <span class="badge bg-secondary">Sem permissões</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination Section -->
  <?php if (!empty($paginacao)) : ?>
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <?php foreach ($paginacao as $pagina) : ?>
          <?php $class = $pagina['atual'] ? 'btn-primary' : 'btn-outline-secondary'; ?>
          <a href="index.php?r=home&pagina=<?= $pagina['pagina'] ?><?= $queryString ? '&' . $queryString : '' ?>">
            <button type="button" class="btn <?= $class ?> me-2"><?= $pagina['pagina'] ?></button>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>
</div>

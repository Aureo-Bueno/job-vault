<?php
$mensagem = '';
if (isset($_GET['status'])) {
  switch ($_GET['status']) {
    case 'success':
      $mensagem = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                     <i class="bi bi-check-circle-fill"></i> Ação executada com sucesso!
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                   </div>';
      break;
    case 'error':
      $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                     <i class="bi bi-exclamation-circle-fill"></i> Não foi possível executar ação!
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                   </div>';
      break;
  }
}

$resultados = '';
foreach ($vagas as $vaga) {
  $acoes = '';

  if ($podeEditar) {
    $acoes .= '<a href="editar.php?id=' . $vaga->id . '" class="me-2">
                 <button type="button" class="btn btn-primary btn-sm">
                   <i class="bi bi-pencil-fill"></i> Editar
                 </button>
               </a>';
  }

  if ($podeDeletar) {
    $acoes .= '<a href="excluir.php?id=' . $vaga->id . '">
                 <button type="button" class="btn btn-danger btn-sm">
                   <i class="bi bi-trash-fill"></i> Excluir
                 </button>
               </a>';
  }

  if (empty($acoes)) {
    $acoes = '<span class="badge bg-secondary">Sem permissões</span>';
  }

  $badgeClass = $vaga->ativo == 's' ? 'bg-success' : 'bg-secondary';
  $statusText = $vaga->ativo == 's' ? 'Ativo' : 'Inativo';

  $resultados .= '<tr class="align-middle">
                    <td><strong>#' . $vaga->id . '</strong></td>
                    <td><strong>' . htmlspecialchars($vaga->titulo) . '</strong></td>
                    <td class="text-muted small">' . htmlspecialchars(substr($vaga->descricao, 0, 50)) . '...</td>
                    <td><span class="badge ' . $badgeClass . '">' . $statusText . '</span></td>
                    <td class="small">' . date('d/m/Y H:i', strtotime($vaga->data)) . '</td>
                    <td>' . $acoes . '</td>
                  </tr>';
}

$resultados = strlen($resultados) ? $resultados : '<tr>
                                                      <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox"></i> Nenhuma vaga encontrada no sistema!
                                                      </td>
                                                    </tr>';

// Gets
unset($_GET['status']);
unset($_GET['pagina']);
$gets = http_build_query($_GET);

// Pagination
$paginacao = '';
$paginas = $obPagination->getPages();
foreach ($paginas as $key => $pagina) {
  $class = $pagina['atual'] ? 'btn-primary' : 'btn-outline-secondary';
  $paginacao .= '<a href="?pagina=' . $pagina['pagina'] . '&' . $gets . '">
                   <button type="button" class="btn ' . $class . ' me-2">' . $pagina['pagina'] . '</button>
                 </a>';
}
?>

<div class="container-content p-5">
  <!-- Alerts -->
  <?= $mensagem ?>

  <!-- Header Section -->
  <div class="row mb-4 align-items-center">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-briefcase-fill text-primary"></i> Vagas de Emprego
      </h2>
      <p class="text-muted small mb-0">Gerencie as vagas do seu sistema</p>
    </div>
    <?php if ($podeCriar): ?>
      <div class="col-auto">
        <a href="cadastrar.php">
          <button class="btn btn-success fw-bold">
            <i class="bi bi-plus-circle-fill"></i> Nova Vaga
          </button>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Filter Section -->
  <div class="card border-0 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
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
  <div class="card border-0 shadow-sm" style="background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(10px);">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="border-bottom" style="border-color: rgba(255, 255, 255, 0.1);">
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
          <?= $resultados ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination Section -->
  <?php if (!empty($paginacao)): ?>
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <?= $paginacao ?>
      </nav>
    </div>
  <?php endif; ?>
</div>

<style>
  .form-control,
  .form-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    transition: all 0.3s ease;
  }

  .form-control:focus,
  .form-select:focus {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(13, 110, 253, 0.5);
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  }

  .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
  }

  .form-label {
    font-weight: 600;
    font-size: 0.95rem;
  }

  .table {
    color: rgba(255, 255, 255, 0.85);
  }

  .table tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: background-color 0.3s ease;
  }

  .table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.03);
  }

  .btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #667eea 100%);
    border: none;
  }

  .btn-primary:hover {
    background: linear-gradient(135deg, #0056c4 0%, #5568d3 100%);
    transform: translateY(-2px);
  }

  .btn-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border: none;
  }

  .btn-success:hover {
    background: linear-gradient(135deg, #157347 0%, #1aa179 100%);
    transform: translateY(-2px);
  }

  .btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
    border: none;
  }

  .btn-danger:hover {
    background: linear-gradient(135deg, #bb2d3b 0%, #ff5252 100%);
    transform: translateY(-2px);
  }

  .btn-outline-secondary {
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
  }

  .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
  }

  .badge {
    font-weight: 600;
    padding: 0.35rem 0.75rem;
    font-size: 0.85rem;
  }

  .card {
    border-radius: 12px;
  }
</style>
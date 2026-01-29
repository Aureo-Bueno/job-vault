<?php
// Expecting: $alerta, $usuarios, $rolesById, $podeEditar, $podeDeletar, $podeCriar, $paginacao, $busca, $queryString
?>

<div class="container-content p-5">
  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show" role="alert">
      <i class="<?= htmlspecialchars($alerta['icone']) ?>"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row mb-4 align-items-center">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-people-fill text-primary"></i> Usuários
      </h2>
      <p class="text-muted small mb-0">Gerencie os usuários do sistema</p>
    </div>
    <?php if ($podeCriar) : ?>
      <div class="col-auto">
        <a href="index.php?r=usuarios/novo">
          <button class="btn btn-primary fw-bold">
            <i class="bi bi-person-plus-fill"></i> Novo Usuário
          </button>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-9">
          <label for="busca" class="form-label text-white">
            <i class="bi bi-search"></i> Buscar por Nome ou Email
          </label>
          <input type="text" name="busca" id="busca" class="form-control" placeholder="Digite nome ou email..." value="<?= htmlspecialchars($busca) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="bi bi-search"></i> Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="border-bottom">
          <tr>
            <th class="text-white fw-bold">ID</th>
            <th class="text-white fw-bold">Nome</th>
            <th class="text-white fw-bold">Email</th>
            <th class="text-white fw-bold">Role</th>
            <th class="text-white fw-bold">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios)) : ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="bi bi-inbox"></i> Nenhum usuário encontrado.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($usuarios as $usuario) : ?>
              <?php
              $roleNome = $rolesById[$usuario->roleId ?? null] ?? 'Sem role';
              ?>
              <tr class="align-middle">
                <td><strong>#<?= (int) $usuario->id ?></strong></td>
                <td><?= htmlspecialchars($usuario->nome ?? '') ?></td>
                <td><?= htmlspecialchars($usuario->email ?? '') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($roleNome) ?></span></td>
                <td>
                  <?php if ($podeEditar) : ?>
                    <a href="index.php?r=usuarios/editar&id=<?= (int) $usuario->id ?>" class="me-2">
                      <button type="button" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-fill"></i> Editar
                      </button>
                    </a>
                  <?php endif; ?>

                  <?php if ($podeDeletar) : ?>
                    <a href="index.php?r=usuarios/excluir&id=<?= (int) $usuario->id ?>">
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

  <?php if (!empty($paginacao)) : ?>
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <?php foreach ($paginacao as $pagina) : ?>
          <?php $class = $pagina['atual'] ? 'btn-primary' : 'btn-outline-secondary'; ?>
          <a href="index.php?r=usuarios&pagina=<?= $pagina['pagina'] ?><?= $queryString ? '&' . $queryString : '' ?>">
            <button type="button" class="btn <?= $class ?> me-2"><?= $pagina['pagina'] ?></button>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>
</div>

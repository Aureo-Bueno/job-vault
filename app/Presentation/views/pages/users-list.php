<?php
// Expecting: $alerta, $users, $rolesById, $canEdit, $canDelete, $canCreate, $pagination, $search, $queryString
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
    <?php if ($canCreate) : ?>
      <div class="col-auto">
        <a href="index.php?r=users/new">
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
          <label for="search" class="form-label text-white">
            <i class="bi bi-search"></i> Buscar por Nome ou Email
          </label>
          <input type="text" name="search" id="search" class="form-control" placeholder="Digite nome ou email..." value="<?= htmlspecialchars($search) ?>">
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
          <?php if (empty($users)) : ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="bi bi-inbox"></i> Nenhum usuário encontrado.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($users as $index => $user) : ?>
              <?php
              $roleName = $rolesById[$user->roleId ?? null] ?? 'Sem role';
              $userId = (string) ($user->id ?? '');
              ?>
              <tr class="align-middle">
                <td><strong>#<?= $index + 1 ?></strong></td>
                <td><?= htmlspecialchars($user->name ?? '') ?></td>
                <td><?= htmlspecialchars($user->email ?? '') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($roleName) ?></span></td>
                <td>
                  <?php if ($canEdit) : ?>
                    <a href="index.php?r=users/edit&id=<?= htmlspecialchars($userId) ?>" class="me-2">
                      <button type="button" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-fill"></i> Editar
                      </button>
                    </a>
                  <?php endif; ?>

                  <?php if ($canDelete) : ?>
                    <a href="index.php?r=users/delete&id=<?= htmlspecialchars($userId) ?>">
                      <button type="button" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash-fill"></i> Excluir
                      </button>
                    </a>
                  <?php endif; ?>

                  <?php if (!$canEdit && !$canDelete) : ?>
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

  <?php if (!empty($pagination)) : ?>
    <div class="d-flex justify-content-center mt-4">
      <nav>
        <?php foreach ($pagination as $pagina) : ?>
          <?php $class = $pagina['atual'] ? 'btn-primary' : 'btn-outline-secondary'; ?>
          <a href="index.php?r=users&pagina=<?= $pagina['pagina'] ?><?= $queryString ? '&' . $queryString : '' ?>">
            <button type="button" class="btn <?= $class ?> me-2"><?= $pagina['pagina'] ?></button>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>
</div>

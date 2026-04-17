<?php
// Admin panel for users, roles and permissions.

$baseQuery = [
  'r' => 'users',
  'search' => $search,
  'role_filter' => $roleFilter,
  'role_search' => $roleSearch,
  'permission_search' => $permissionSearch,
  'permission_module_filter' => $permissionModuleFilter,
];

$buildUrl = static function (array $extra = []) use ($baseQuery): string {
  $query = array_merge($baseQuery, $extra);
  return 'index.php?' . http_build_query($query);
};
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
      <h2 class="section-title"><i class="bi bi-shield-lock-fill text-info"></i> Painel administrativo de usuários</h2>
      <p class="section-subtitle">Tela exclusiva de administradores com controle por permissão em cada ação.</p>
    </div>
  </div>

  <div class="alert alert-info mb-4" role="alert">
    <i class="bi bi-info-circle-fill me-1"></i>
    Elementos e ações são exibidos somente quando a permissão correspondente está ativa no perfil do admin.
  </div>
</section>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h3 class="section-title"><i class="bi bi-people-fill text-info"></i> Usuários</h3>
      <p class="section-subtitle">CRUD completo de usuários com filtros e atribuição de role.</p>
    </div>
  </div>

  <?php if (!$canListUsers) : ?>
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      Permissão `user.list` não encontrada. Listagem de usuários ocultada.
    </div>
  <?php else : ?>
    <div class="card toolbar-card">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <input type="hidden" name="r" value="users">
          <input type="hidden" name="role_search" value="<?= htmlspecialchars($roleSearch) ?>">
          <input type="hidden" name="permission_search" value="<?= htmlspecialchars($permissionSearch) ?>">
          <input type="hidden" name="permission_module_filter" value="<?= htmlspecialchars($permissionModuleFilter) ?>">

          <div class="col-12 col-lg-5">
            <label for="user-search" class="form-label"><i class="bi bi-search"></i> Buscar usuário</label>
            <input id="user-search" type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Nome ou e-mail">
          </div>

          <div class="col-12 col-lg-4">
            <label for="role-filter" class="form-label"><i class="bi bi-funnel"></i> Filtrar por role</label>
            <select id="role-filter" name="role_filter" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($roles as $role) : ?>
                <?php $roleId = (string) ($role->id ?? ''); ?>
                <option value="<?= htmlspecialchars($roleId) ?>" <?= $roleFilter === $roleId ? 'selected' : '' ?>>
                  <?= htmlspecialchars($role->name ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-3 d-grid">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-funnel-fill"></i> Filtrar usuários
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($canCreateUsers) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <h4 class="h6 text-white mb-3"><i class="bi bi-person-plus-fill"></i> Criar usuário</h4>
          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="user_create">

            <div class="col-12 col-lg-3">
              <label class="form-label" for="new-user-name">Nome <span class="text-danger">*</span></label>
              <input id="new-user-name" type="text" name="name" class="form-control" required>
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="new-user-email">E-mail <span class="text-danger">*</span></label>
              <input id="new-user-email" type="email" name="email" class="form-control" required>
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="new-user-password">Senha <span class="text-danger">*</span></label>
              <input id="new-user-password" type="password" name="password" class="form-control" required minlength="6">
            </div>

            <?php if ($canAssignRoleToUser) : ?>
              <div class="col-12 col-lg-3">
                <label class="form-label" for="new-user-role">Role</label>
                <select id="new-user-role" name="role_id" class="form-select">
                  <option value="">Sem role</option>
                  <?php foreach ($roles as $role) : ?>
                    <option value="<?= htmlspecialchars((string) ($role->id ?? '')) ?>"><?= htmlspecialchars($role->name ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="col-12 d-grid d-lg-flex justify-content-lg-end">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg"></i> Salvar usuário
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($editingUser && $canEditUsers) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="h6 text-white mb-0"><i class="bi bi-pencil-fill"></i> Editando usuário: <?= htmlspecialchars($editingUser->name ?? '') ?></h4>
            <a href="<?= htmlspecialchars($buildUrl(['edit_user_id' => null])) ?>" class="btn btn-outline-secondary btn-sm">Cancelar edição</a>
          </div>

          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="user_update">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($editingUser->id ?? '')) ?>">

            <div class="col-12 col-lg-3">
              <label class="form-label" for="edit-user-name">Nome <span class="text-danger">*</span></label>
              <input id="edit-user-name" type="text" name="name" class="form-control" value="<?= htmlspecialchars($editingUser->name ?? '') ?>" required>
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="edit-user-email">E-mail <span class="text-danger">*</span></label>
              <input id="edit-user-email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($editingUser->email ?? '') ?>" required>
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="edit-user-password">Nova senha</label>
              <input id="edit-user-password" type="password" name="password" class="form-control" placeholder="Opcional" minlength="6">
            </div>

            <?php if ($canAssignRoleToUser) : ?>
              <div class="col-12 col-lg-3">
                <label class="form-label" for="edit-user-role">Role</label>
                <select id="edit-user-role" name="role_id" class="form-select">
                  <option value="" <?= empty($editingUser->roleId) ? 'selected' : '' ?>>Sem role</option>
                  <?php foreach ($roles as $role) : ?>
                    <?php $roleId = (string) ($role->id ?? ''); ?>
                    <option value="<?= htmlspecialchars($roleId) ?>" <?= ($editingUser->roleId ?? '') === $roleId ? 'selected' : '' ?>>
                      <?= htmlspecialchars($role->name ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="col-12 d-grid d-lg-flex justify-content-lg-end">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg"></i> Atualizar usuário
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Role</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)) : ?>
              <tr>
                <td colspan="5" class="empty-state">
                  <i class="bi bi-inbox"></i> Nenhum usuário encontrado para os filtros aplicados.
                </td>
              </tr>
            <?php else : ?>
              <?php foreach ($users as $index => $user) : ?>
                <?php
                $userId = (string) ($user->id ?? '');
                $isCurrentUser = $loggedUserId === $userId;
                $userRoleName = $rolesById[$user->roleId ?? ''] ?? 'Sem role';
                $editLink = $buildUrl(['edit_user_id' => $userId]);
                ?>
                <tr>
                  <td><strong><?= $index + 1 ?></strong></td>
                  <td>
                    <strong><?= htmlspecialchars($user->name ?? '') ?></strong>
                    <?php if ($isCurrentUser) : ?>
                      <span class="badge status-badge bg-info text-dark ms-1">Você</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted"><?= htmlspecialchars($user->email ?? '') ?></td>
                  <td><span class="badge status-badge bg-secondary"><?= htmlspecialchars($userRoleName) ?></span></td>
                  <td>
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                      <?php if ($canEditUsers) : ?>
                        <a href="<?= htmlspecialchars($editLink) ?>" class="btn btn-primary btn-sm">
                          <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                      <?php endif; ?>

                      <?php if ($canDeleteUsers && !$isCurrentUser) : ?>
                        <form method="POST" class="d-inline">
                          <?= \App\Util\Csrf::input() ?>
                          <input type="hidden" name="action" value="user_delete">
                          <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Confirma a exclusão deste usuário?');">
                            <i class="bi bi-trash-fill"></i> Excluir
                          </button>
                        </form>
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
      <nav class="d-flex justify-content-center mt-4 app-pagination" aria-label="Paginação de usuários">
        <div class="d-flex gap-2 flex-wrap justify-content-center">
          <?php foreach ($pagination as $page) : ?>
            <?php $class = $page['atual'] ? 'btn-primary' : 'btn-outline-secondary'; ?>
            <a href="index.php?r=users&pagina=<?= $page['pagina'] ?><?= $queryString ? '&' . $queryString : '' ?>" class="btn <?= $class ?>">
              <?= $page['pagina'] ?>
            </a>
          <?php endforeach; ?>
        </div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h3 class="section-title"><i class="bi bi-diagram-3-fill text-info"></i> Roles</h3>
      <p class="section-subtitle">Criação, edição, exclusão e vínculo de permissões por role.</p>
    </div>
  </div>

  <?php if (!$canListRoles) : ?>
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      Permissão para listar roles não encontrada. Seção de roles ocultada.
    </div>
  <?php else : ?>
    <div class="card toolbar-card">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <input type="hidden" name="r" value="users">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
          <input type="hidden" name="role_filter" value="<?= htmlspecialchars($roleFilter) ?>">
          <input type="hidden" name="permission_search" value="<?= htmlspecialchars($permissionSearch) ?>">
          <input type="hidden" name="permission_module_filter" value="<?= htmlspecialchars($permissionModuleFilter) ?>">

          <div class="col-12 col-lg-9">
            <label for="role-search" class="form-label"><i class="bi bi-search"></i> Buscar role</label>
            <input id="role-search" type="text" name="role_search" class="form-control" value="<?= htmlspecialchars($roleSearch) ?>" placeholder="Nome ou descrição da role">
          </div>

          <div class="col-12 col-lg-3 d-grid">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-funnel-fill"></i> Filtrar roles
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($canCreateRoles) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <h4 class="h6 text-white mb-3"><i class="bi bi-plus-square-fill"></i> Criar role</h4>
          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="role_create">

            <div class="col-12 col-lg-4">
              <label class="form-label" for="new-role-name">Nome da role <span class="text-danger">*</span></label>
              <input id="new-role-name" type="text" name="role_name" class="form-control" required>
            </div>

            <div class="col-12 col-lg-6">
              <label class="form-label" for="new-role-description">Descrição</label>
              <input id="new-role-description" type="text" name="role_description" class="form-control">
            </div>

            <div class="col-12 col-lg-2 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Criar
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($editingRole && $canEditRoles) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="h6 text-white mb-0"><i class="bi bi-pencil-fill"></i> Editando role: <?= htmlspecialchars($editingRole->name ?? '') ?></h4>
            <a href="<?= htmlspecialchars($buildUrl(['edit_role_id' => null])) ?>" class="btn btn-outline-secondary btn-sm">Cancelar edição</a>
          </div>

          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="role_update">
            <input type="hidden" name="role_id" value="<?= htmlspecialchars((string) ($editingRole->id ?? '')) ?>">

            <div class="col-12 col-lg-4">
              <label class="form-label" for="edit-role-name">Nome da role <span class="text-danger">*</span></label>
              <input id="edit-role-name" type="text" name="role_name" class="form-control" value="<?= htmlspecialchars($editingRole->name ?? '') ?>" required>
            </div>

            <div class="col-12 col-lg-6">
              <label class="form-label" for="edit-role-description">Descrição</label>
              <input id="edit-role-description" type="text" name="role_description" class="form-control" value="<?= htmlspecialchars($editingRole->description ?? '') ?>">
            </div>

            <div class="col-12 col-lg-2 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Atualizar
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Role</th>
              <th>Descrição</th>
              <th>Usuários</th>
              <th>Permissões</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filteredRoles)) : ?>
              <tr>
                <td colspan="5" class="empty-state">
                  <i class="bi bi-inbox"></i> Nenhuma role encontrada para os filtros aplicados.
                </td>
              </tr>
            <?php else : ?>
              <?php foreach ($filteredRoles as $role) : ?>
                <?php
                $roleId = (string) ($role->id ?? '');
                $rolePermissions = $permissionsByRoleId[$roleId] ?? [];
                $assignedPermissionIds = $permissionIdsByRoleId[$roleId] ?? [];
                $roleUsers = $roleUserCount[$roleId] ?? 0;
                $editRoleLink = $buildUrl(['edit_role_id' => $roleId]);
                ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($role->name ?? '') ?></strong>
                    <?php if (($role->name ?? '') === 'admin') : ?>
                      <span class="badge status-badge bg-info text-dark ms-1">Sistema</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted"><?= htmlspecialchars($role->description ?? 'Sem descrição') ?></td>
                  <td><span class="badge status-badge bg-secondary"><?= (int) $roleUsers ?></span></td>
                  <td>
                    <?php if (empty($rolePermissions)) : ?>
                      <span class="badge status-badge bg-secondary">Sem permissões</span>
                    <?php else : ?>
                      <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($rolePermissions as $permission) : ?>
                          <span class="badge status-badge bg-primary">
                            <?= htmlspecialchars($permission->name ?? '') ?>
                          </span>
                          <?php if ($canAssignPermissionToRole && $permission->id) : ?>
                            <form method="POST" class="d-inline">
                              <?= \App\Util\Csrf::input() ?>
                              <input type="hidden" name="action" value="role_permission_remove">
                              <input type="hidden" name="role_id" value="<?= htmlspecialchars($roleId) ?>">
                              <input type="hidden" name="permission_id" value="<?= htmlspecialchars((string) $permission->id) ?>">
                              <button type="submit" class="btn btn-outline-secondary btn-sm" title="Remover permissão">
                                <i class="bi bi-x-lg"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($canAssignPermissionToRole && !empty($permissions)) : ?>
                      <form method="POST" class="d-flex gap-2 mt-2">
                        <?= \App\Util\Csrf::input() ?>
                        <input type="hidden" name="action" value="role_permission_assign">
                        <input type="hidden" name="role_id" value="<?= htmlspecialchars($roleId) ?>">
                        <select name="permission_id" class="form-select form-select-sm" required>
                          <option value="">Selecionar permissão</option>
                          <?php foreach ($permissions as $permissionOption) : ?>
                            <?php
                            $permissionId = (string) ($permissionOption->id ?? '');
                            if ($permissionId === '' || in_array($permissionId, $assignedPermissionIds, true)) {
                              continue;
                            }
                            ?>
                            <option value="<?= htmlspecialchars($permissionId) ?>">
                              <?= htmlspecialchars($permissionOption->name ?? '') ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">Vincular</button>
                      </form>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                      <?php if ($canEditRoles) : ?>
                        <a href="<?= htmlspecialchars($editRoleLink) ?>" class="btn btn-primary btn-sm">
                          <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                      <?php endif; ?>

                      <?php if ($canDeleteRoles) : ?>
                        <form method="POST" class="d-inline">
                          <?= \App\Util\Csrf::input() ?>
                          <input type="hidden" name="action" value="role_delete">
                          <input type="hidden" name="role_id" value="<?= htmlspecialchars($roleId) ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Confirma a exclusão desta role?');">
                            <i class="bi bi-trash-fill"></i> Excluir
                          </button>
                        </form>
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
  <?php endif; ?>
</section>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h3 class="section-title"><i class="bi bi-key-fill text-info"></i> Permissões</h3>
      <p class="section-subtitle">CRUD de permissões para governar ações do painel.</p>
    </div>
  </div>

  <?php if (!$canListPermissions) : ?>
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      Permissão para listar permissões não encontrada. Seção ocultada.
    </div>
  <?php else : ?>
    <div class="card toolbar-card">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <input type="hidden" name="r" value="users">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
          <input type="hidden" name="role_filter" value="<?= htmlspecialchars($roleFilter) ?>">
          <input type="hidden" name="role_search" value="<?= htmlspecialchars($roleSearch) ?>">

          <div class="col-12 col-lg-5">
            <label for="permission-search" class="form-label"><i class="bi bi-search"></i> Buscar permissão</label>
            <input id="permission-search" type="text" name="permission_search" class="form-control" value="<?= htmlspecialchars($permissionSearch) ?>" placeholder="Nome, módulo, ação ou descrição">
          </div>

          <div class="col-12 col-lg-4">
            <label for="permission-module-filter" class="form-label"><i class="bi bi-funnel"></i> Módulo</label>
            <select id="permission-module-filter" name="permission_module_filter" class="form-select">
              <option value="">Todos</option>
              <?php foreach ($permissionModules as $module) : ?>
                <option value="<?= htmlspecialchars($module) ?>" <?= $permissionModuleFilter === $module ? 'selected' : '' ?>>
                  <?= htmlspecialchars($module) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-3 d-grid">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-funnel-fill"></i> Filtrar permissões
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($canCreatePermissions) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <h4 class="h6 text-white mb-3"><i class="bi bi-plus-square-fill"></i> Criar permissão</h4>
          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="permission_create">

            <div class="col-12 col-lg-3">
              <label class="form-label" for="new-permission-name">Nome (ex: user.create) <span class="text-danger">*</span></label>
              <input id="new-permission-name" type="text" name="permission_name" class="form-control" required>
            </div>

            <div class="col-12 col-lg-2">
              <label class="form-label" for="new-permission-module">Módulo</label>
              <input id="new-permission-module" type="text" name="permission_module" class="form-control" placeholder="user">
            </div>

            <div class="col-12 col-lg-2">
              <label class="form-label" for="new-permission-action">Ação</label>
              <input id="new-permission-action" type="text" name="permission_action" class="form-control" placeholder="create">
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="new-permission-description">Descrição</label>
              <input id="new-permission-description" type="text" name="permission_description" class="form-control">
            </div>

            <div class="col-12 col-lg-2 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Criar
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($editingPermission && $canEditPermissions) : ?>
      <div class="card toolbar-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="h6 text-white mb-0"><i class="bi bi-pencil-fill"></i> Editando permissão: <?= htmlspecialchars($editingPermission->name ?? '') ?></h4>
            <a href="<?= htmlspecialchars($buildUrl(['edit_permission_id' => null])) ?>" class="btn btn-outline-secondary btn-sm">Cancelar edição</a>
          </div>

          <form method="POST" class="row g-3">
            <?= \App\Util\Csrf::input() ?>
            <input type="hidden" name="action" value="permission_update">
            <input type="hidden" name="permission_id" value="<?= htmlspecialchars((string) ($editingPermission->id ?? '')) ?>">

            <div class="col-12 col-lg-3">
              <label class="form-label" for="edit-permission-name">Nome <span class="text-danger">*</span></label>
              <input id="edit-permission-name" type="text" name="permission_name" class="form-control" value="<?= htmlspecialchars($editingPermission->name ?? '') ?>" required>
            </div>

            <div class="col-12 col-lg-2">
              <label class="form-label" for="edit-permission-module">Módulo</label>
              <input id="edit-permission-module" type="text" name="permission_module" class="form-control" value="<?= htmlspecialchars($editingPermission->module ?? '') ?>">
            </div>

            <div class="col-12 col-lg-2">
              <label class="form-label" for="edit-permission-action">Ação</label>
              <input id="edit-permission-action" type="text" name="permission_action" class="form-control" value="<?= htmlspecialchars($editingPermission->action ?? '') ?>">
            </div>

            <div class="col-12 col-lg-3">
              <label class="form-label" for="edit-permission-description">Descrição</label>
              <input id="edit-permission-description" type="text" name="permission_description" class="form-control" value="<?= htmlspecialchars($editingPermission->description ?? '') ?>">
            </div>

            <div class="col-12 col-lg-2 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Atualizar
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Módulo</th>
              <th>Ação</th>
              <th>Descrição</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($filteredPermissions)) : ?>
              <tr>
                <td colspan="5" class="empty-state">
                  <i class="bi bi-inbox"></i> Nenhuma permissão encontrada para os filtros aplicados.
                </td>
              </tr>
            <?php else : ?>
              <?php foreach ($filteredPermissions as $permission) : ?>
                <?php
                $permissionId = (string) ($permission->id ?? '');
                $editPermissionLink = $buildUrl(['edit_permission_id' => $permissionId]);
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars($permission->name ?? '') ?></strong></td>
                  <td><span class="badge status-badge bg-secondary"><?= htmlspecialchars($permission->module ?? '-') ?></span></td>
                  <td><span class="badge status-badge bg-secondary"><?= htmlspecialchars($permission->action ?? '-') ?></span></td>
                  <td class="text-muted"><?= htmlspecialchars($permission->description ?? 'Sem descrição') ?></td>
                  <td>
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                      <?php if ($canEditPermissions) : ?>
                        <a href="<?= htmlspecialchars($editPermissionLink) ?>" class="btn btn-primary btn-sm">
                          <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                      <?php endif; ?>

                      <?php if ($canDeletePermissions) : ?>
                        <form method="POST" class="d-inline">
                          <?= \App\Util\Csrf::input() ?>
                          <input type="hidden" name="action" value="permission_delete">
                          <input type="hidden" name="permission_id" value="<?= htmlspecialchars($permissionId) ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Confirma a exclusão desta permissão?');">
                            <i class="bi bi-trash-fill"></i> Excluir
                          </button>
                        </form>
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
  <?php endif; ?>
</section>

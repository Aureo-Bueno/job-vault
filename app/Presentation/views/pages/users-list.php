<?php
/**
 * Admin panel: users, companies, roles and permissions (one tab per section).
 *
 * @var array|null $alerta
 * @var string $tab
 * @var string[] $tabs
 * @var int $totalUsers
 * @var string $loggedUserId
 * @var \App\Application\DTOs\UserDto[] $users
 * @var \App\Application\DTOs\CompanyDto[] $companies
 * @var array<string,string> $companiesById
 * @var \App\Application\DTOs\RoleDto[] $roles
 * @var \App\Application\DTOs\RoleDto[] $filteredRoles
 * @var \App\Application\DTOs\PermissionDto[] $permissions
 * @var \App\Application\DTOs\PermissionDto[] $filteredPermissions
 * @var array<string,string> $rolesById
 * @var array<string,\App\Application\DTOs\PermissionDto[]> $permissionsByRoleId
 * @var array<string,string[]> $permissionIdsByRoleId
 * @var array<string,int> $roleUserCount
 * @var string[] $permissionModules
 * @var \App\Application\DTOs\UserDto|null $editingUser
 * @var \App\Application\DTOs\CompanyDto|null $editingCompany
 * @var \App\Application\DTOs\RoleDto|null $editingRole
 * @var \App\Application\DTOs\PermissionDto|null $editingPermission
 * @var bool $canManageCompanies
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$baseQuery = [
  'r' => 'users',
  'tab' => $tab,
  'search' => $search,
  'role_filter' => $roleFilter,
  'role_search' => $roleSearch,
  'permission_search' => $permissionSearch,
  'permission_module_filter' => $permissionModuleFilter,
];
$buildUrl = static fn (array $extra = []): string => 'index.php?' . http_build_query(array_filter(
  array_merge($baseQuery, $extra),
  static fn ($value): bool => $value !== null && $value !== ''
));

$tabLabels = [
  'users' => ['Usuários', 'bi-people', $totalUsers],
  'companies' => ['Empresas', 'bi-building', count($companies)],
  'roles' => ['Roles', 'bi-diagram-3', count($roles)],
  'permissions' => ['Permissões', 'bi-key', count($permissions)],
];
$roleBadge = static fn (string $name): string => 'jv-badge--' . preg_replace('/[^a-z]/', '', strtolower($name));
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow">Administração</p>
    <h1 class="jv-title">Usuários, empresas e acessos</h1>
    <p class="jv-subtitle">Gerencie contas, empresas recrutadoras, papéis e permissões do sistema.</p>
  </div>
</header>

<?php View::partial('alert', ['alerta' => $alerta]); ?>

<nav class="jv-tabs" aria-label="Seções do painel">
  <?php foreach ($tabs as $key) : ?>
    <?php [$label, $icon, $count] = $tabLabels[$key]; ?>
    <a class="jv-tab <?= $tab === $key ? 'active' : '' ?>" href="index.php?r=users&amp;tab=<?= $key ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
      <i class="bi <?= $icon ?>" aria-hidden="true"></i> <?= $label ?> <span class="jv-count"><?= (int) $count ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'users') : ?>
  <?php if ($editingUser && $canEditUsers) : ?>
    <section class="jv-panel mb-3">
      <div class="jv-panel-head">
        <h2 class="jv-section-title">Editar <?= htmlspecialchars($editingUser->name) ?></h2>
        <a href="<?= htmlspecialchars($buildUrl(['edit_user_id' => null])) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
      </div>
      <form method="POST" class="jv-panel-body">
        <?= Csrf::input() ?>
        <input type="hidden" name="action" value="user_update">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($editingUser->id) ?>">
        <div class="jv-form-grid">
          <div>
            <label class="form-label" for="edit-user-name">Nome</label>
            <input id="edit-user-name" type="text" name="name" class="form-control" value="<?= htmlspecialchars($editingUser->name) ?>" required>
          </div>
          <div>
            <label class="form-label" for="edit-user-email">E-mail</label>
            <input id="edit-user-email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($editingUser->email) ?>" required>
          </div>
          <div>
            <label class="form-label" for="edit-user-password">Nova senha</label>
            <input id="edit-user-password" type="password" name="password" class="form-control" placeholder="Manter a atual" minlength="6" autocomplete="new-password">
          </div>
          <?php if ($canAssignRoleToUser) : ?>
            <div>
              <label class="form-label" for="edit-user-role">Role</label>
              <select id="edit-user-role" name="role_id" class="form-select">
                <option value="" <?= empty($editingUser->roleId) ? 'selected' : '' ?>>Sem role</option>
                <?php foreach ($roles as $role) : ?>
                  <option value="<?= htmlspecialchars($role->id) ?>" <?= $editingUser->roleId === $role->id ? 'selected' : '' ?>><?= htmlspecialchars($role->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div>
            <label class="form-label" for="edit-user-company">Empresa vinculada</label>
            <select id="edit-user-company" name="company_id" class="form-select">
              <option value="" <?= empty($editingUser->companyId) ? 'selected' : '' ?>>Nenhuma (Candidato / Admin)</option>
              <?php foreach ($companies as $company) : ?>
                <option value="<?= htmlspecialchars($company->id) ?>" <?= ($editingUser->companyId ?? '') === $company->id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($company->name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="jv-form-actions">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Salvar alterações</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <section class="jv-panel">
    <div class="jv-panel-head">
      <h2 class="jv-section-title">Usuários</h2>
      <?php if ($canCreateUsers) : ?>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#new-user" aria-expanded="false" aria-controls="new-user">
          <i class="bi bi-plus-lg"></i> Novo usuário
        </button>
      <?php endif; ?>
    </div>

    <?php if ($canCreateUsers) : ?>
      <div class="collapse" id="new-user">
        <form method="POST" class="jv-panel-body border-bottom">
          <?= Csrf::input() ?>
          <input type="hidden" name="action" value="user_create">
          <div class="jv-form-grid">
            <div>
              <label class="form-label" for="new-user-name">Nome</label>
              <input id="new-user-name" type="text" name="name" class="form-control" required>
            </div>
            <div>
              <label class="form-label" for="new-user-email">E-mail</label>
              <input id="new-user-email" type="email" name="email" class="form-control" required>
            </div>
            <div>
              <label class="form-label" for="new-user-password">Senha</label>
              <input id="new-user-password" type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <?php if ($canAssignRoleToUser) : ?>
              <div>
                <label class="form-label" for="new-user-role">Role</label>
                <select id="new-user-role" name="role_id" class="form-select">
                  <option value="">Padrão (usuario)</option>
                  <?php foreach ($roles as $role) : ?>
                    <option value="<?= htmlspecialchars($role->id) ?>"><?= htmlspecialchars($role->name) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <div>
              <label class="form-label" for="new-user-company">Empresa vinculada (opcional)</label>
              <select id="new-user-company" name="company_id" class="form-select">
                <option value="">Nenhuma</option>
                <?php foreach ($companies as $company) : ?>
                  <option value="<?= htmlspecialchars($company->id) ?>"><?= htmlspecialchars($company->name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="jv-form-actions">
            <button type="button" class="btn btn-ghost" data-bs-toggle="collapse" data-bs-target="#new-user">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Criar usuário</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <?php if (!$canListUsers) : ?>
      <div class="jv-empty">
        <i class="bi bi-eye-slash" aria-hidden="true"></i>
        <strong>Listagem indisponível</strong>
        Seu perfil não tem a permissão <span class="jv-mono">user.list</span>.
      </div>
    <?php else : ?>
      <form method="GET" class="jv-toolbar" role="search">
        <input type="hidden" name="r" value="users">
        <input type="hidden" name="tab" value="users">
        <label class="jv-search mb-0">
          <span class="visually-hidden">Buscar usuário</span>
          <i class="bi bi-search" aria-hidden="true"></i>
          <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nome ou e-mail">
        </label>
        <label class="visually-hidden" for="role-filter">Filtrar por role</label>
        <select id="role-filter" name="role_filter" class="form-select">
          <option value="">Todas as roles</option>
          <?php foreach ($roles as $role) : ?>
            <option value="<?= htmlspecialchars($role->id) ?>" <?= $roleFilter === $role->id ? 'selected' : '' ?>><?= htmlspecialchars($role->name) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        <?php if ($search !== '' || $roleFilter !== '') : ?>
          <a href="index.php?r=users&amp;tab=users" class="btn btn-link text-muted px-2">Limpar</a>
        <?php endif; ?>
      </form>

      <?php if (empty($users)) : ?>
        <div class="jv-empty">
          <i class="bi bi-people" aria-hidden="true"></i>
          <strong>Nenhum usuário encontrado</strong>
          Ajuste a busca ou o filtro de role.
        </div>
      <?php else : ?>
        <div class="table-responsive">
          <table class="table jv-table jv-table-stack table-hover">
            <thead>
              <tr>
                <th scope="col">Usuário</th>
                <th scope="col">Role</th>
                <th scope="col">Empresa</th>
                <th scope="col" class="text-end"><span class="visually-hidden">Ações</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user) : ?>
                <?php
                $isCurrentUser = $loggedUserId === $user->id;
                $userRoleName = $rolesById[$user->roleId ?? ''] ?? null;
                $userCompanyName = $companiesById[$user->companyId ?? ''] ?? null;
                ?>
                <tr>
                  <td data-label="">
                    <div class="jv-user-cell">
                      <span class="jv-avatar jv-avatar--sm" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($user->name, 0, 1))) ?></span>
                      <div class="min-w-0">
                        <span class="jv-cell-title">
                          <?= htmlspecialchars($user->name) ?>
                          <?php if ($isCurrentUser) : ?><span class="jv-badge jv-badge--info ms-1">você</span><?php endif; ?>
                        </span>
                        <span class="jv-cell-sub"><?= htmlspecialchars($user->email) ?></span>
                      </div>
                    </div>
                  </td>
                  <td data-label="Role">
                    <?php if ($userRoleName !== null) : ?>
                      <span class="jv-badge <?= htmlspecialchars($roleBadge($userRoleName)) ?>"><?= htmlspecialchars($userRoleName) ?></span>
                    <?php else : ?>
                      <span class="text-muted small">Sem role</span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Empresa">
                    <?php if ($userCompanyName !== null) : ?>
                      <span class="jv-badge jv-badge--info"><i class="bi bi-building me-1"></i><?= htmlspecialchars($userCompanyName) ?></span>
                    <?php else : ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td data-label="">
                    <div class="jv-actions">
                      <?php if ($canEditUsers) : ?>
                        <a href="<?= htmlspecialchars($buildUrl(['edit_user_id' => $user->id])) ?>" class="jv-icon-btn" title="Editar" aria-label="Editar <?= htmlspecialchars($user->name) ?>">
                          <i class="bi bi-pencil"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($canDeleteUsers && !$isCurrentUser) : ?>
                        <form method="POST" data-confirm="Excluir o usuário <?= htmlspecialchars($user->name) ?>? Essa ação não pode ser desfeita.">
                          <?= Csrf::input() ?>
                          <input type="hidden" name="action" value="user_delete">
                          <input type="hidden" name="user_id" value="<?= htmlspecialchars($user->id) ?>">
                          <button type="submit" class="jv-icon-btn jv-icon-btn--danger" title="Excluir" aria-label="Excluir <?= htmlspecialchars($user->name) ?>">
                            <i class="bi bi-trash3"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php View::partial('pagination', [
          'pagination' => $pagination,
          'baseUrl' => 'index.php?r=users',
          'queryString' => $queryString,
          'summary' => $totalUsers === 1 ? '1 usuário' : $totalUsers . ' usuários',
        ]); ?>
      <?php endif; ?>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php if ($tab === 'companies') : ?>
  <?php if ($editingCompany && $canManageCompanies) : ?>
    <section class="jv-panel mb-3">
      <div class="jv-panel-head">
        <h2 class="jv-section-title">Editar empresa <?= htmlspecialchars($editingCompany->name) ?></h2>
        <a href="<?= htmlspecialchars($buildUrl(['edit_company_id' => null])) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
      </div>
      <form method="POST" class="jv-panel-body">
        <?= Csrf::input() ?>
        <input type="hidden" name="action" value="company_update">
        <input type="hidden" name="company_id" value="<?= htmlspecialchars($editingCompany->id) ?>">
        <div class="jv-form-grid">
          <div>
            <label class="form-label" for="edit-company-name">Nome da empresa</label>
            <input id="edit-company-name" type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($editingCompany->name) ?>" required>
          </div>
          <div>
            <label class="form-label" for="edit-company-sector">Setor</label>
            <input id="edit-company-sector" type="text" name="company_sector" class="form-control" value="<?= htmlspecialchars($editingCompany->sector) ?>" required>
          </div>
          <div>
            <label class="form-label" for="edit-company-website">Site (opcional)</label>
            <input id="edit-company-website" type="url" name="company_website" class="form-control" value="<?= htmlspecialchars($editingCompany->website) ?>" placeholder="https://">
          </div>
          <div>
            <label class="form-label" for="edit-company-description">Descrição</label>
            <input id="edit-company-description" type="text" name="company_description" class="form-control" value="<?= htmlspecialchars($editingCompany->description) ?>">
          </div>
        </div>
        <div class="jv-form-actions">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Salvar alterações</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <section class="jv-panel">
    <div class="jv-panel-head">
      <h2 class="jv-section-title">Empresas cadastradas</h2>
      <?php if ($canManageCompanies) : ?>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#new-company" aria-expanded="false" aria-controls="new-company">
          <i class="bi bi-plus-lg"></i> Nova empresa
        </button>
      <?php endif; ?>
    </div>

    <?php if ($canManageCompanies) : ?>
      <div class="collapse" id="new-company">
        <form method="POST" class="jv-panel-body border-bottom">
          <?= Csrf::input() ?>
          <input type="hidden" name="action" value="company_create">
          <div class="jv-form-grid">
            <div>
              <label class="form-label" for="new-company-name">Nome da empresa</label>
              <input id="new-company-name" type="text" name="company_name" class="form-control" placeholder="Ex.: TechNova Sistemas" required>
            </div>
            <div>
              <label class="form-label" for="new-company-sector">Setor</label>
              <input id="new-company-sector" type="text" name="company_sector" class="form-control" value="Tecnologia" required>
            </div>
            <div>
              <label class="form-label" for="new-company-website">Site (opcional)</label>
              <input id="new-company-website" type="url" name="company_website" class="form-control" placeholder="https://empresa.com.br">
            </div>
            <div>
              <label class="form-label" for="new-company-description">Descrição</label>
              <input id="new-company-description" type="text" name="company_description" class="form-control" placeholder="Resumo da empresa">
            </div>
          </div>
          <div class="jv-form-actions">
            <button type="button" class="btn btn-ghost" data-bs-toggle="collapse" data-bs-target="#new-company">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Criar empresa</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <?php if (empty($companies)) : ?>
      <div class="jv-empty">
        <i class="bi bi-building" aria-hidden="true"></i>
        <strong>Nenhuma empresa cadastrada</strong>
        Crie uma empresa acima ou vincule recrutadores na aba de usuários.
      </div>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table jv-table jv-table-stack table-hover">
          <thead>
            <tr>
              <th scope="col">Empresa</th>
              <th scope="col">Setor</th>
              <th scope="col">Recrutadores</th>
              <th scope="col" class="text-end"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($companies as $company) : ?>
              <tr>
                <td data-label="">
                  <span class="jv-cell-title">
                    <?= htmlspecialchars($company->name) ?>
                    <?php if ($company->website !== '') : ?>
                      <a href="<?= htmlspecialchars($company->website) ?>" target="_blank" rel="noopener noreferrer" class="ms-1 small text-info text-decoration-none" title="Abrir site">
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                    <?php endif; ?>
                  </span>
                  <span class="jv-cell-sub"><?= htmlspecialchars($company->description !== '' ? $company->description : 'Sem descrição') ?></span>
                </td>
                <td data-label="Setor"><span class="jv-badge jv-badge--info"><?= htmlspecialchars($company->sector) ?></span></td>
                <td data-label="Recrutadores"><span class="jv-mono"><?= (int) $company->userCount ?></span></td>
                <td data-label="">
                  <div class="jv-actions">
                    <?php if ($canManageCompanies) : ?>
                      <a href="<?= htmlspecialchars($buildUrl(['edit_company_id' => $company->id])) ?>" class="jv-icon-btn" title="Editar" aria-label="Editar empresa <?= htmlspecialchars($company->name) ?>">
                        <i class="bi bi-pencil"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canDeleteUsers) : ?>
                      <form method="POST" data-confirm="Excluir a empresa <?= htmlspecialchars($company->name) ?>? As vagas dela também serão removidas.">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="company_delete">
                        <input type="hidden" name="company_id" value="<?= htmlspecialchars($company->id) ?>">
                        <button type="submit" class="jv-icon-btn jv-icon-btn--danger" title="Excluir" aria-label="Excluir empresa <?= htmlspecialchars($company->name) ?>">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php if ($tab === 'roles') : ?>
  <?php if ($editingRole && $canEditRoles) : ?>
    <section class="jv-panel mb-3">
      <div class="jv-panel-head">
        <h2 class="jv-section-title">Editar role <span class="jv-mono text-muted"><?= htmlspecialchars($editingRole->name) ?></span></h2>
        <a href="<?= htmlspecialchars($buildUrl(['edit_role_id' => null])) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
      </div>
      <form method="POST" class="jv-panel-body">
        <?= Csrf::input() ?>
        <input type="hidden" name="action" value="role_update">
        <input type="hidden" name="role_id" value="<?= htmlspecialchars($editingRole->id) ?>">
        <div class="jv-form-grid">
          <div>
            <label class="form-label" for="edit-role-name">Nome</label>
            <input id="edit-role-name" type="text" name="role_name" class="form-control" value="<?= htmlspecialchars($editingRole->name) ?>" required <?= $editingRole->isSystem ? 'readonly' : '' ?>>
            <?php if ($editingRole->isSystem) : ?><div class="form-text">A role admin não pode ser renomeada.</div><?php endif; ?>
          </div>
          <div>
            <label class="form-label" for="edit-role-description">Descrição</label>
            <input id="edit-role-description" type="text" name="role_description" class="form-control" value="<?= htmlspecialchars($editingRole->description) ?>">
          </div>
        </div>
        <div class="jv-form-actions">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Salvar alterações</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <section class="jv-panel">
    <div class="jv-panel-head">
      <h2 class="jv-section-title">Roles</h2>
      <?php if ($canCreateRoles) : ?>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#new-role" aria-expanded="false" aria-controls="new-role">
          <i class="bi bi-plus-lg"></i> Nova role
        </button>
      <?php endif; ?>
    </div>

    <?php if ($canCreateRoles) : ?>
      <div class="collapse" id="new-role">
        <form method="POST" class="jv-panel-body border-bottom">
          <?= Csrf::input() ?>
          <input type="hidden" name="action" value="role_create">
          <div class="jv-form-grid">
            <div>
              <label class="form-label" for="new-role-name">Nome</label>
              <input id="new-role-name" type="text" name="role_name" class="form-control" placeholder="Ex.: recrutador" required>
            </div>
            <div>
              <label class="form-label" for="new-role-description">Descrição</label>
              <input id="new-role-description" type="text" name="role_description" class="form-control" placeholder="Opcional">
            </div>
          </div>
          <div class="jv-form-actions">
            <button type="button" class="btn btn-ghost" data-bs-toggle="collapse" data-bs-target="#new-role">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Criar role</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <form method="GET" class="jv-toolbar" role="search">
      <input type="hidden" name="r" value="users">
      <input type="hidden" name="tab" value="roles">
      <label class="jv-search mb-0">
        <span class="visually-hidden">Buscar role</span>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="role_search" class="form-control" value="<?= htmlspecialchars($roleSearch) ?>" placeholder="Buscar por nome ou descrição">
      </label>
      <button class="btn btn-ghost" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
      <?php if ($roleSearch !== '') : ?>
        <a href="index.php?r=users&amp;tab=roles" class="btn btn-link text-muted px-2">Limpar</a>
      <?php endif; ?>
    </form>

    <?php if (empty($filteredRoles)) : ?>
      <div class="jv-empty">
        <i class="bi bi-diagram-3" aria-hidden="true"></i>
        <strong>Nenhuma role encontrada</strong>
        Ajuste a busca ou crie uma nova role.
      </div>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table jv-table jv-table-stack">
          <thead>
            <tr>
              <th scope="col">Role</th>
              <th scope="col">Usuários</th>
              <th scope="col">Permissões</th>
              <th scope="col" class="text-end"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredRoles as $role) : ?>
              <?php
              $rolePermissions = $permissionsByRoleId[$role->id] ?? [];
              $assignedIds = $permissionIdsByRoleId[$role->id] ?? [];
              $available = array_filter($permissions, static fn ($p): bool => !in_array($p->id, $assignedIds, true));
              ?>
              <tr>
                <td data-label="" class="jv-col-role">
                  <span class="jv-cell-title">
                    <span class="jv-badge <?= htmlspecialchars($roleBadge($role->name)) ?>"><?= htmlspecialchars($role->name) ?></span>
                    <?php if ($role->isSystem) : ?><span class="jv-badge ms-1"><i class="bi bi-lock"></i> sistema</span><?php endif; ?>
                  </span>
                  <span class="jv-cell-sub"><?= htmlspecialchars($role->description !== '' ? $role->description : 'Sem descrição') ?></span>
                </td>
                <td data-label="Usuários"><span class="jv-mono"><?= (int) ($roleUserCount[$role->id] ?? 0) ?></span></td>
                <td data-label="Permissões" class="jv-col-perms">
                  <?php if (empty($rolePermissions)) : ?>
                    <span class="text-muted small">Nenhuma permissão</span>
                  <?php else : ?>
                    <div class="jv-chips">
                      <?php foreach ($rolePermissions as $permission) : ?>
                        <span class="jv-chip <?= $canAssignPermissionToRole ? '' : 'jv-chip--static' ?>">
                          <?= htmlspecialchars($permission->name) ?>
                          <?php if ($canAssignPermissionToRole) : ?>
                            <form method="POST">
                              <?= Csrf::input() ?>
                              <input type="hidden" name="action" value="role_permission_remove">
                              <input type="hidden" name="role_id" value="<?= htmlspecialchars($role->id) ?>">
                              <input type="hidden" name="permission_id" value="<?= htmlspecialchars($permission->id) ?>">
                              <button type="submit" title="Remover" aria-label="Remover <?= htmlspecialchars($permission->name) ?> de <?= htmlspecialchars($role->name) ?>"><i class="bi bi-x-lg"></i></button>
                            </form>
                          <?php endif; ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <?php if ($canAssignPermissionToRole && !empty($available)) : ?>
                    <form method="POST" class="jv-assign">
                      <?= Csrf::input() ?>
                      <input type="hidden" name="action" value="role_permission_assign">
                      <input type="hidden" name="role_id" value="<?= htmlspecialchars($role->id) ?>">
                      <label class="visually-hidden" for="assign-<?= htmlspecialchars($role->id) ?>">Adicionar permissão a <?= htmlspecialchars($role->name) ?></label>
                      <select id="assign-<?= htmlspecialchars($role->id) ?>" name="permission_id" class="form-select form-select-sm" required>
                        <option value="">Adicionar permissão…</option>
                        <?php foreach ($available as $option) : ?>
                          <option value="<?= htmlspecialchars($option->id) ?>"><?= htmlspecialchars($option->name) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-ghost btn-sm">Adicionar</button>
                    </form>
                  <?php endif; ?>
                </td>
                <td data-label="">
                  <div class="jv-actions">
                    <?php if ($canEditRoles) : ?>
                      <a href="<?= htmlspecialchars($buildUrl(['edit_role_id' => $role->id])) ?>" class="jv-icon-btn" title="Editar" aria-label="Editar role <?= htmlspecialchars($role->name) ?>">
                        <i class="bi bi-pencil"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canDeleteRoles && !$role->isSystem) : ?>
                      <form method="POST" data-confirm="Excluir a role <?= htmlspecialchars($role->name) ?>?">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="role_delete">
                        <input type="hidden" name="role_id" value="<?= htmlspecialchars($role->id) ?>">
                        <button type="submit" class="jv-icon-btn jv-icon-btn--danger" title="Excluir" aria-label="Excluir role <?= htmlspecialchars($role->name) ?>">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php if ($tab === 'permissions') : ?>
  <?php if ($editingPermission && $canEditPermissions) : ?>
    <section class="jv-panel mb-3">
      <div class="jv-panel-head">
        <h2 class="jv-section-title">Editar permissão <span class="jv-mono text-muted"><?= htmlspecialchars($editingPermission->name) ?></span></h2>
        <a href="<?= htmlspecialchars($buildUrl(['edit_permission_id' => null])) ?>" class="btn btn-ghost btn-sm">Cancelar</a>
      </div>
      <form method="POST" class="jv-panel-body">
        <?= Csrf::input() ?>
        <input type="hidden" name="action" value="permission_update">
        <input type="hidden" name="permission_id" value="<?= htmlspecialchars($editingPermission->id) ?>">
        <div class="jv-form-grid">
          <div>
            <label class="form-label" for="edit-permission-name">Nome</label>
            <input id="edit-permission-name" type="text" name="permission_name" class="form-control" value="<?= htmlspecialchars($editingPermission->name) ?>">
          </div>
          <div>
            <label class="form-label" for="edit-permission-module">Módulo</label>
            <input id="edit-permission-module" type="text" name="permission_module" class="form-control" value="<?= htmlspecialchars($editingPermission->module) ?>">
          </div>
          <div>
            <label class="form-label" for="edit-permission-action">Ação</label>
            <input id="edit-permission-action" type="text" name="permission_action" class="form-control" value="<?= htmlspecialchars($editingPermission->action) ?>">
          </div>
          <div>
            <label class="form-label" for="edit-permission-description">Descrição</label>
            <input id="edit-permission-description" type="text" name="permission_description" class="form-control" value="<?= htmlspecialchars($editingPermission->description) ?>">
          </div>
        </div>
        <div class="jv-form-actions">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Salvar alterações</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <section class="jv-panel">
    <div class="jv-panel-head">
      <h2 class="jv-section-title">Permissões</h2>
      <?php if ($canCreatePermissions) : ?>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#new-permission" aria-expanded="false" aria-controls="new-permission">
          <i class="bi bi-plus-lg"></i> Nova permissão
        </button>
      <?php endif; ?>
    </div>

    <?php if ($canCreatePermissions) : ?>
      <div class="collapse" id="new-permission">
        <form method="POST" class="jv-panel-body border-bottom">
          <?= Csrf::input() ?>
          <input type="hidden" name="action" value="permission_create">
          <div class="jv-form-grid">
            <div>
              <label class="form-label" for="new-permission-module">Módulo</label>
              <input id="new-permission-module" type="text" name="permission_module" class="form-control" placeholder="vacancy">
            </div>
            <div>
              <label class="form-label" for="new-permission-action">Ação</label>
              <input id="new-permission-action" type="text" name="permission_action" class="form-control" placeholder="publish">
            </div>
            <div>
              <label class="form-label" for="new-permission-name">Nome</label>
              <input id="new-permission-name" type="text" name="permission_name" class="form-control" placeholder="Gerado: módulo.ação" aria-describedby="new-permission-name-help">
              <div id="new-permission-name-help" class="form-text">Deixe vazio para usar módulo.ação.</div>
            </div>
            <div>
              <label class="form-label" for="new-permission-description">Descrição</label>
              <input id="new-permission-description" type="text" name="permission_description" class="form-control" placeholder="Opcional">
            </div>
          </div>
          <div class="jv-form-actions">
            <button type="button" class="btn btn-ghost" data-bs-toggle="collapse" data-bs-target="#new-permission">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Criar permissão</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <form method="GET" class="jv-toolbar" role="search">
      <input type="hidden" name="r" value="users">
      <input type="hidden" name="tab" value="permissions">
      <label class="jv-search mb-0">
        <span class="visually-hidden">Buscar permissão</span>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="permission_search" class="form-control" value="<?= htmlspecialchars($permissionSearch) ?>" placeholder="Buscar por nome, módulo, ação ou descrição">
      </label>
      <label class="visually-hidden" for="permission-module-filter">Módulo</label>
      <select id="permission-module-filter" name="permission_module_filter" class="form-select">
        <option value="">Todos os módulos</option>
        <?php foreach ($permissionModules as $module) : ?>
          <option value="<?= htmlspecialchars($module) ?>" <?= $permissionModuleFilter === $module ? 'selected' : '' ?>><?= htmlspecialchars($module) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-ghost" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
      <?php if ($permissionSearch !== '' || $permissionModuleFilter !== '') : ?>
        <a href="index.php?r=users&amp;tab=permissions" class="btn btn-link text-muted px-2">Limpar</a>
      <?php endif; ?>
    </form>

    <?php if (empty($filteredPermissions)) : ?>
      <div class="jv-empty">
        <i class="bi bi-key" aria-hidden="true"></i>
        <strong>Nenhuma permissão encontrada</strong>
        Ajuste a busca ou o módulo.
      </div>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table jv-table jv-table-stack table-hover">
          <thead>
            <tr>
              <th scope="col">Permissão</th>
              <th scope="col">Módulo</th>
              <th scope="col">Ação</th>
              <th scope="col" class="text-end"><span class="visually-hidden">Ações</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredPermissions as $permission) : ?>
              <tr>
                <td data-label="">
                  <span class="jv-cell-title jv-mono"><?= htmlspecialchars($permission->name) ?></span>
                  <span class="jv-cell-sub"><?= htmlspecialchars($permission->description !== '' ? $permission->description : 'Sem descrição') ?></span>
                </td>
                <td data-label="Módulo"><span class="jv-badge"><?= htmlspecialchars($permission->module !== '' ? $permission->module : '—') ?></span></td>
                <td data-label="Ação"><span class="jv-mono text-muted"><?= htmlspecialchars($permission->action !== '' ? $permission->action : '—') ?></span></td>
                <td data-label="">
                  <div class="jv-actions">
                    <?php if ($canEditPermissions) : ?>
                      <a href="<?= htmlspecialchars($buildUrl(['edit_permission_id' => $permission->id])) ?>" class="jv-icon-btn" title="Editar" aria-label="Editar <?= htmlspecialchars($permission->name) ?>">
                        <i class="bi bi-pencil"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canDeletePermissions) : ?>
                      <form method="POST" data-confirm="Excluir a permissão <?= htmlspecialchars($permission->name) ?>? Ela será removida de todas as roles.">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="permission_delete">
                        <input type="hidden" name="permission_id" value="<?= htmlspecialchars($permission->id) ?>">
                        <button type="submit" class="jv-icon-btn jv-icon-btn--danger" title="Excluir" aria-label="Excluir <?= htmlspecialchars($permission->name) ?>">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php

/**
 * Admin panel: users, companies, roles and permissions.
 *
 * Buttons/sections are shown according to the user's permissions, but every action is
 * authorized again (and only) by the command bus.
 */

use App\Application\Commands\Companies\CreateCompanyCommand;
use App\Application\Commands\Companies\DeleteCompanyCommand;
use App\Application\Commands\Companies\UpdateCompanyCommand;
use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\Commands\Permissions\DeletePermissionCommand;
use App\Application\Commands\Permissions\UpdatePermissionCommand;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\Commands\Roles\DeleteRoleCommand;
use App\Application\Commands\Roles\UpdateRoleCommand;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\DTOs\CompanyDto;
use App\Application\DTOs\PermissionDto;
use App\Application\DTOs\RoleDto;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Queries\Users\SearchUsersQuery;
use App\Application\Security\MessageAuthorizer;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\Pagination;
use App\Presentation\Support\StatusAlertMapper;

$loggedUser = PageAccess::requireLogin();
PageAccess::requireAdmin($loggedUser);

$commandBus = AppContainer::commandBus();
$queryBus = AppContainer::queryBus();
$post = static fn (string $key): string => trim((string) ($_POST[$key] ?? ''));

$canAssignRoleToUser = PageAccess::can($loggedUser, MessageAuthorizer::ASSIGN_ROLE);
$orAssign = static fn (string $permission): bool => $canAssignRoleToUser || PageAccess::can($loggedUser, $permission);

$canListUsers = PageAccess::can($loggedUser, 'user.list');
$canCreateUsers = PageAccess::canAny($loggedUser, ['user.create', 'user.edit']);
$canEditUsers = PageAccess::can($loggedUser, 'user.edit');
$canDeleteUsers = PageAccess::can($loggedUser, 'user.delete');
$canListCompanies = $canListUsers || $canCreateUsers || $canAssignRoleToUser;
$canManageCompanies = $canCreateUsers || $canEditUsers || $canAssignRoleToUser;
$canListRoles = $orAssign('role.list');
$canCreateRoles = $orAssign('role.create');
$canEditRoles = $orAssign('role.edit');
$canDeleteRoles = $orAssign('role.delete');
$canAssignPermissionToRole = $orAssign('role.assign_permission');
$canListPermissions = $orAssign('permission.list');
$canCreatePermissions = $orAssign('permission.create');
$canEditPermissions = $orAssign('permission.edit');
$canDeletePermissions = $orAssign('permission.delete');

// Sections of the panel; each one is shown only when the admin can list it.
$tabs = array_filter([
  'users' => $canListUsers || $canCreateUsers,
  'companies' => $canListCompanies,
  'roles' => $canListRoles,
  'permissions' => $canListPermissions,
]);
$requestedTab = (string) ($_GET['tab'] ?? $_POST['tab'] ?? '');
$tab = isset($tabs[$requestedTab]) ? $requestedTab : (array_key_first($tabs) ?? 'users');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $actionName = $post('action');
  $actionTab = match (true) {
    str_starts_with($actionName, 'company_') => 'companies',
    str_starts_with($actionName, 'role_') => 'roles',
    str_starts_with($actionName, 'permission_') => 'permissions',
    default => 'users',
  };
  $back = ['tab' => $actionTab];

  if (!Csrf::validateFromRequest()) {
    HttpRedirect::withError('users', new MessageValidationException(
      'Falha na validação da requisição. Recarregue a página e tente novamente.'
    ), $back);
  }

  /** @var array<string,callable():array{0:object,1:string}> $actions */
  $actions = [
    'user_create' => static fn (): array => [new CreateUserCommand(
      $post('name'),
      $post('email'),
      (string) ($_POST['password'] ?? ''),
      $canAssignRoleToUser && $post('role_id') !== '' ? $post('role_id') : null,
      $post('company_id') !== '' ? $post('company_id') : null
    ), 'Usuário criado com sucesso.'],
    'user_update' => static fn (): array => [new UpdateUserCommand(
      $post('user_id'),
      $post('name'),
      $post('email'),
      ($_POST['password'] ?? '') !== '' ? (string) $_POST['password'] : null,
      $post('role_id') !== '' ? $post('role_id') : null,
      $canAssignRoleToUser,
      $post('company_id') !== '' ? $post('company_id') : null,
      true
    ), 'Usuário atualizado com sucesso.'],
    'user_delete' => static fn (): array => [new DeleteUserCommand($post('user_id')), 'Usuário excluído com sucesso.'],
    'company_create' => static fn (): array => [new CreateCompanyCommand(
      $post('company_name'),
      $post('company_sector') !== '' ? $post('company_sector') : 'Tecnologia',
      $post('company_website'),
      $post('company_description')
    ), 'Empresa criada com sucesso.'],
    'company_update' => static fn (): array => [new UpdateCompanyCommand(
      $post('company_id'),
      $post('company_name'),
      $post('company_sector') !== '' ? $post('company_sector') : 'Tecnologia',
      $post('company_website'),
      $post('company_description')
    ), 'Empresa atualizada com sucesso.'],
    'company_delete' => static fn (): array => [new DeleteCompanyCommand($post('company_id')), 'Empresa removida com sucesso.'],
    'role_create' => static fn (): array => [
      new CreateRoleCommand($post('role_name'), $post('role_description')),
      'Role criada com sucesso.',
    ],
    'role_update' => static fn (): array => [
      new UpdateRoleCommand($post('role_id'), $post('role_name'), $post('role_description')),
      'Role atualizada com sucesso.',
    ],
    'role_delete' => static fn (): array => [new DeleteRoleCommand($post('role_id')), 'Role removida com sucesso.'],
    'permission_create' => static fn (): array => [new CreatePermissionCommand(
      $post('permission_name'),
      $post('permission_description'),
      $post('permission_module'),
      $post('permission_action')
    ), 'Permissão criada com sucesso.'],
    'permission_update' => static fn (): array => [new UpdatePermissionCommand(
      $post('permission_id'),
      $post('permission_name'),
      $post('permission_description'),
      $post('permission_module'),
      $post('permission_action')
    ), 'Permissão atualizada com sucesso.'],
    'permission_delete' => static fn (): array => [
      new DeletePermissionCommand($post('permission_id')),
      'Permissão removida com sucesso.',
    ],
    'role_permission_assign' => static fn (): array => [
      new AssignPermissionToRoleCommand($post('role_id'), $post('permission_id')),
      'Permissão vinculada à role com sucesso.',
    ],
    'role_permission_remove' => static fn (): array => [
      new RemovePermissionFromRoleCommand($post('role_id'), $post('permission_id')),
      'Permissão removida da role com sucesso.',
    ],
  ];

  $action = $actions[$actionName] ?? null;
  if ($action === null) {
    HttpRedirect::withError('users', new MessageValidationException('Ação inválida.'), $back);
  }

  try {
    [$command, $successMessage] = $action();
    $commandBus->dispatch($command);
  } catch (\Throwable $exception) {
    HttpRedirect::withError('users', $exception, $back);
  }

  HttpRedirect::withSuccess('users', $successMessage, $back);
}

$search = trim((string) ($_GET['search'] ?? ''));
$roleFilter = trim((string) ($_GET['role_filter'] ?? ''));
$roleSearch = trim((string) ($_GET['role_search'] ?? ''));
$permissionSearch = trim((string) ($_GET['permission_search'] ?? ''));
$permissionModuleFilter = trim((string) ($_GET['permission_module_filter'] ?? ''));

/** @var CompanyDto[] $companies */
$companies = $canListCompanies ? $queryBus->ask(new ListCompaniesQuery()) : [];
$companiesById = [];
foreach ($companies as $company) {
  $companiesById[$company->id] = $company->name;
}

/** @var RoleDto[] $roles */
$roles = $canListRoles ? $queryBus->ask(new ListRolesQuery()) : [];
$filteredRoles = $roleSearch !== '' && $canListRoles ? $queryBus->ask(new ListRolesQuery($roleSearch)) : $roles;

/** @var PermissionDto[] $permissions */
$permissions = $canListPermissions ? $queryBus->ask(new ListPermissionsQuery()) : [];
$filteredPermissions = ($permissionSearch !== '' || $permissionModuleFilter !== '') && $canListPermissions
  ? $queryBus->ask(new ListPermissionsQuery($permissionModuleFilter, $permissionSearch))
  : $permissions;

$permissionsById = [];
foreach ($permissions as $permission) {
  $permissionsById[$permission->id] = $permission;
}

$rolesById = [];
$permissionsByRoleId = [];
$permissionIdsByRoleId = [];
$roleUserCount = [];
foreach ($roles as $role) {
  $rolesById[$role->id] = $role->name;
  $permissionIdsByRoleId[$role->id] = $role->permissionIds;
  $roleUserCount[$role->id] = $role->userCount;
  $permissionsByRoleId[$role->id] = array_values(array_filter(array_map(
    static fn (string $id): ?PermissionDto => $permissionsById[$id] ?? null,
    $role->permissionIds
  )));
}

$users = [];
$paginationPages = [];
$totalUsers = 0;
if ($canListUsers) {
  $result = $queryBus->ask(new SearchUsersQuery($search, $roleFilter, Pagination::currentPage(), 8));
  $users = $result->items;
  $totalUsers = $result->total;
  $paginationPages = Pagination::links($result);
}

$permissionModules = array_values(array_unique(array_filter(array_map(
  static fn (PermissionDto $permission): string => $permission->module,
  $permissions
))));
sort($permissionModules);

$editUserId = (string) ($_GET['edit_user_id'] ?? '');
$editingUser = $canEditUsers && $editUserId !== '' ? $queryBus->ask(new GetUserByIdQuery($editUserId)) : null;

$editCompanyId = (string) ($_GET['edit_company_id'] ?? '');
$editingCompany = null;
foreach ($canManageCompanies ? $companies : [] as $company) {
  if ($company->id === $editCompanyId) {
    $editingCompany = $company;
  }
}

$editRoleId = (string) ($_GET['edit_role_id'] ?? '');
$editingRole = null;
foreach ($canEditRoles ? $roles : [] as $role) {
  if ($role->id === $editRoleId) {
    $editingRole = $role;
  }
}

$editPermissionId = (string) ($_GET['edit_permission_id'] ?? '');
$editingPermission = $canEditPermissions ? ($permissionsById[$editPermissionId] ?? null) : null;

Layout::page('/pages/users-list.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'tab' => $tab,
  'tabs' => array_keys($tabs),
  'totalUsers' => $totalUsers,
  'loggedUserId' => $loggedUser->id,
  'users' => $users,
  'companies' => $companies,
  'companiesById' => $companiesById,
  'roles' => $roles,
  'filteredRoles' => $filteredRoles,
  'permissions' => $permissions,
  'filteredPermissions' => $filteredPermissions,
  'rolesById' => $rolesById,
  'permissionsByRoleId' => $permissionsByRoleId,
  'permissionIdsByRoleId' => $permissionIdsByRoleId,
  'roleUserCount' => $roleUserCount,
  'permissionModules' => $permissionModules,
  'editingUser' => $editingUser,
  'editingCompany' => $editingCompany,
  'editingRole' => $editingRole,
  'editingPermission' => $editingPermission,
  'pagination' => $paginationPages,
  'search' => $search,
  'roleFilter' => $roleFilter,
  'roleSearch' => $roleSearch,
  'permissionSearch' => $permissionSearch,
  'permissionModuleFilter' => $permissionModuleFilter,
  'queryString' => Pagination::preservedQuery(),
  'canListUsers' => $canListUsers,
  'canCreateUsers' => $canCreateUsers,
  'canEditUsers' => $canEditUsers,
  'canDeleteUsers' => $canDeleteUsers,
  'canManageCompanies' => $canManageCompanies,
  'canAssignRoleToUser' => $canAssignRoleToUser,
  'canListRoles' => $canListRoles,
  'canCreateRoles' => $canCreateRoles,
  'canEditRoles' => $canEditRoles,
  'canDeleteRoles' => $canDeleteRoles,
  'canAssignPermissionToRole' => $canAssignPermissionToRole,
  'canListPermissions' => $canListPermissions,
  'canCreatePermissions' => $canCreatePermissions,
  'canEditPermissions' => $canEditPermissions,
  'canDeletePermissions' => $canDeletePermissions,
]);

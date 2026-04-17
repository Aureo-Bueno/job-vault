<?php

/**
 * Administrative users page controller.
 *
 * Responsibilities:
 * - restricts access to administrators;
 * - enforces action-level permissions for users, roles and permissions CRUD;
 * - prepares filtered data for the admin dashboard view.
 */

require BASE_PATH . '/vendor/autoload.php';

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
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\RolePermissions\ListPermissionsByRoleQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\CountUsersQuery;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Queries\Users\ListUsersQuery;
use App\Db\Pagination;
use App\Domain\ValueObject\SearchTerm;
use App\Infrastructure\Container\AppContainer;
use App\Infrastructure\Persistence\SqlCriteria;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\StatusAlertMapper;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$loggedUserId = (string) ($loggedUser['id'] ?? '');

if (!RoleManager::isAdmin($loggedUserId)) {
  http_response_code(403);
  View::render(VIEW_PATH . '/layout/header.php');
  echo '<section class="container-content page-section p-3 p-lg-4">';
  echo '<div class="alert alert-danger" role="alert"><i class="bi bi-shield-lock-fill me-1"></i>Acesso restrito ao perfil administrador.</div>';
  echo '</section>';
  View::render(VIEW_PATH . '/layout/footer.php');
  exit;
}

$commandBus = AppContainer::commandBus();
$queryBus = AppContainer::queryBus();

$canListUsers = RoleManager::hasPermission($loggedUserId, 'user.list');
$canCreateUsers = RoleManager::hasPermission($loggedUserId, 'user.create')
  || RoleManager::hasPermission($loggedUserId, 'user.edit');
$canEditUsers = RoleManager::hasPermission($loggedUserId, 'user.edit');
$canDeleteUsers = RoleManager::hasPermission($loggedUserId, 'user.delete');
$canAssignRoleToUser = RoleManager::hasPermission($loggedUserId, 'user.assign_role');

$canListRoles = RoleManager::hasPermission($loggedUserId, 'role.list') || $canAssignRoleToUser;
$canCreateRoles = RoleManager::hasPermission($loggedUserId, 'role.create') || $canAssignRoleToUser;
$canEditRoles = RoleManager::hasPermission($loggedUserId, 'role.edit') || $canAssignRoleToUser;
$canDeleteRoles = RoleManager::hasPermission($loggedUserId, 'role.delete') || $canAssignRoleToUser;
$canAssignPermissionToRole = RoleManager::hasPermission($loggedUserId, 'role.assign_permission') || $canAssignRoleToUser;

$canListPermissions = RoleManager::hasPermission($loggedUserId, 'permission.list') || $canAssignRoleToUser;
$canCreatePermissions = RoleManager::hasPermission($loggedUserId, 'permission.create') || $canAssignRoleToUser;
$canEditPermissions = RoleManager::hasPermission($loggedUserId, 'permission.edit') || $canAssignRoleToUser;
$canDeletePermissions = RoleManager::hasPermission($loggedUserId, 'permission.delete') || $canAssignRoleToUser;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::validateFromRequest()) {
    redirectUsers('error', 'Falha na validação da requisição. Recarregue a página e tente novamente.');
  }

  $action = (string) ($_POST['action'] ?? '');

  try {
    switch ($action) {
      case 'user_create':
        if (!$canCreateUsers) {
          redirectUsers('error', 'Você não tem permissão para criar usuários.');
        }

        $newUserName = trim((string) ($_POST['name'] ?? ''));
        $newUserEmail = trim((string) ($_POST['email'] ?? ''));
        $newUserPassword = (string) ($_POST['password'] ?? '');
        $newUserRoleId = trim((string) ($_POST['role_id'] ?? ''));

        if ($newUserName === '' || $newUserEmail === '' || $newUserPassword === '') {
          redirectUsers('error', 'Preencha nome, e-mail e senha para criar o usuário.');
        }

        $existingUser = $queryBus->ask(new GetUserByEmailQuery($newUserEmail));
        if ($existingUser) {
          redirectUsers('error', 'O e-mail informado já está em uso.');
        }

        $roleId = null;
        if ($canAssignRoleToUser && $newUserRoleId !== '' && IdValidator::isValid($newUserRoleId)) {
          $roleId = $newUserRoleId;
        }

        $createdUser = $commandBus->dispatch(new CreateUserCommand(
          $newUserName,
          $newUserEmail,
          $newUserPassword,
          $roleId
        ));
        if (!$createdUser) {
          redirectUsers('error', 'Não foi possível criar o usuário.');
        }

        redirectUsers('success', 'Usuário criado com sucesso.');
        break;

      case 'user_update':
        if (!$canEditUsers) {
          redirectUsers('error', 'Você não tem permissão para editar usuários.');
        }

        $targetUserId = (string) ($_POST['user_id'] ?? '');
        if (!IdValidator::isValid($targetUserId)) {
          redirectUsers('error', 'Usuário inválido para edição.');
        }

        $existingUser = $queryBus->ask(new GetUserByIdQuery($targetUserId));
        if (!$existingUser) {
          redirectUsers('error', 'Usuário não encontrado.');
        }

        $updatedName = trim((string) ($_POST['name'] ?? ''));
        $updatedEmail = trim((string) ($_POST['email'] ?? ''));
        $updatedPassword = (string) ($_POST['password'] ?? '');
        $updatedRoleId = trim((string) ($_POST['role_id'] ?? ''));

        if ($updatedName === '' || $updatedEmail === '') {
          redirectUsers('error', 'Nome e e-mail são obrigatórios para atualização.');
        }

        $emailOwner = $queryBus->ask(new GetUserByEmailQuery($updatedEmail));
        if ($emailOwner && $emailOwner->id !== $targetUserId) {
          redirectUsers('error', 'Outro usuário já utiliza esse e-mail.');
        }

        $roleId = $existingUser->roleId;
        if ($canAssignRoleToUser) {
          if ($updatedRoleId === '') {
            $roleId = null;
          } elseif (IdValidator::isValid($updatedRoleId)) {
            $roleId = $updatedRoleId;
          }
        }

        $passwordForUpdate = $updatedPassword !== '' ? $updatedPassword : null;
        $updated = $commandBus->dispatch(new UpdateUserCommand(
          $targetUserId,
          $updatedName,
          $updatedEmail,
          $passwordForUpdate,
          $roleId
        ));
        if (!$updated) {
          redirectUsers('error', 'Não foi possível atualizar o usuário.');
        }

        redirectUsers('success', 'Usuário atualizado com sucesso.');
        break;

      case 'user_delete':
        if (!$canDeleteUsers) {
          redirectUsers('error', 'Você não tem permissão para excluir usuários.');
        }

        $targetUserId = (string) ($_POST['user_id'] ?? '');
        if (!IdValidator::isValid($targetUserId)) {
          redirectUsers('error', 'Usuário inválido para exclusão.');
        }

        if ($targetUserId === $loggedUserId) {
          redirectUsers('error', 'Não é permitido excluir o próprio usuário logado.');
        }

        if (!$commandBus->dispatch(new DeleteUserCommand($targetUserId))) {
          redirectUsers('error', 'Não foi possível excluir o usuário.');
        }

        redirectUsers('success', 'Usuário excluído com sucesso.');
        break;

      case 'role_create':
        if (!$canCreateRoles) {
          redirectUsers('error', 'Você não tem permissão para criar roles.');
        }

        $result = $commandBus->dispatch(new CreateRoleCommand(
          (string) ($_POST['role_name'] ?? ''),
          (string) ($_POST['role_description'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Role criada com sucesso.');
        break;

      case 'role_update':
        if (!$canEditRoles) {
          redirectUsers('error', 'Você não tem permissão para editar roles.');
        }

        $result = $commandBus->dispatch(new UpdateRoleCommand(
          (string) ($_POST['role_id'] ?? ''),
          (string) ($_POST['role_name'] ?? ''),
          (string) ($_POST['role_description'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Role atualizada com sucesso.');
        break;

      case 'role_delete':
        if (!$canDeleteRoles) {
          redirectUsers('error', 'Você não tem permissão para excluir roles.');
        }

        $result = $commandBus->dispatch(new DeleteRoleCommand((string) ($_POST['role_id'] ?? '')));
        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Role removida com sucesso.');
        break;

      case 'permission_create':
        if (!$canCreatePermissions) {
          redirectUsers('error', 'Você não tem permissão para criar permissões.');
        }

        $result = $commandBus->dispatch(new CreatePermissionCommand(
          (string) ($_POST['permission_name'] ?? ''),
          (string) ($_POST['permission_description'] ?? ''),
          (string) ($_POST['permission_module'] ?? ''),
          (string) ($_POST['permission_action'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Permissão criada com sucesso.');
        break;

      case 'permission_update':
        if (!$canEditPermissions) {
          redirectUsers('error', 'Você não tem permissão para editar permissões.');
        }

        $result = $commandBus->dispatch(new UpdatePermissionCommand(
          (string) ($_POST['permission_id'] ?? ''),
          (string) ($_POST['permission_name'] ?? ''),
          (string) ($_POST['permission_description'] ?? ''),
          (string) ($_POST['permission_module'] ?? ''),
          (string) ($_POST['permission_action'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Permissão atualizada com sucesso.');
        break;

      case 'permission_delete':
        if (!$canDeletePermissions) {
          redirectUsers('error', 'Você não tem permissão para excluir permissões.');
        }

        $result = $commandBus->dispatch(new DeletePermissionCommand((string) ($_POST['permission_id'] ?? '')));
        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Permissão removida com sucesso.');
        break;

      case 'role_permission_assign':
        if (!$canAssignPermissionToRole) {
          redirectUsers('error', 'Você não tem permissão para vincular permissões em roles.');
        }

        $result = $commandBus->dispatch(new AssignPermissionToRoleCommand(
          (string) ($_POST['role_id'] ?? ''),
          (string) ($_POST['permission_id'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Permissão vinculada à role com sucesso.');
        break;

      case 'role_permission_remove':
        if (!$canAssignPermissionToRole) {
          redirectUsers('error', 'Você não tem permissão para remover permissões de roles.');
        }

        $result = $commandBus->dispatch(new RemovePermissionFromRoleCommand(
          (string) ($_POST['role_id'] ?? ''),
          (string) ($_POST['permission_id'] ?? '')
        ));

        if (!$result['ok']) {
          redirectUsers('error', (string) $result['error']);
        }

        redirectUsers('success', 'Permissão removida da role com sucesso.');
        break;

      default:
        redirectUsers('error', 'Ação inválida.');
    }
  } catch (\Throwable $exception) {
    if (getenv('APP_TEST_MODE') === '1' && str_starts_with($exception->getMessage(), 'REDIRECT:')) {
      throw $exception;
    }

    redirectUsers('error', $exception->getMessage());
  }
}

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$alerta = StatusAlertMapper::from($status);
$customMessage = trim((string) ($_GET['message'] ?? ''));
if ($alerta && $customMessage !== '') {
  $alerta['mensagem'] = $customMessage;
}

$search = trim((string) ($_GET['search'] ?? ''));
$roleFilter = trim((string) ($_GET['role_filter'] ?? ''));
$roleSearch = trim((string) ($_GET['role_search'] ?? ''));
$permissionSearch = trim((string) ($_GET['permission_search'] ?? ''));
$permissionModuleFilter = trim((string) ($_GET['permission_module_filter'] ?? ''));

$roles = [];
if (
  $canListRoles
  || $canAssignRoleToUser
  || $canCreateRoles
  || $canEditRoles
  || $canDeleteRoles
  || $canAssignPermissionToRole
) {
  $roles = $queryBus->ask(new ListRolesQuery());
}

$permissions = [];
if (
  $canListPermissions
  || $canCreatePermissions
  || $canEditPermissions
  || $canDeletePermissions
  || $canAssignPermissionToRole
) {
  $permissions = $queryBus->ask(new ListPermissionsQuery());
}

$rolesById = [];
foreach ($roles as $role) {
  if ($role->id !== null) {
    $rolesById[$role->id] = $role->name;
  }
}

$userCriteria = new SqlCriteria();
$searchTerm = SearchTerm::fromString($search);
if ($searchTerm) {
  $userCriteria->addContainsAny(['name', 'email'], $searchTerm, 'search_user');
}
if ($roleFilter !== '' && IdValidator::isValid($roleFilter)) {
  $userCriteria->addEquals('role_id', 'role_id', $roleFilter);
}

$users = [];
$paginationPages = [];
if ($canListUsers) {
  $where = $userCriteria->whereClause();
  $parameters = $userCriteria->parameters();

  $totalUsers = $queryBus->ask(new CountUsersQuery($where, $parameters));
  $currentPage = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
  $currentPage = max(1, (int) $currentPage);
  $pagination = new Pagination((int) $totalUsers, $currentPage, 8);
  $paginationPages = $pagination->getPages();

  $users = $queryBus->ask(new ListUsersQuery(
    $where,
    'id DESC',
    $pagination->getLimit(),
    $parameters
  ));
}

$permissionIdsByRoleId = [];
$permissionsByRoleId = [];
$roleUserCount = [];
foreach ($roles as $role) {
  $roleId = (string) ($role->id ?? '');
  if (!IdValidator::isValid($roleId)) {
    continue;
  }

  $rolePermissions = $queryBus->ask(new ListPermissionsByRoleQuery($roleId));
  $permissionsByRoleId[$roleId] = $rolePermissions;

  $assignedPermissionIds = [];
  foreach ($rolePermissions as $permission) {
    if ($permission->id !== null) {
      $assignedPermissionIds[] = $permission->id;
    }
  }
  $permissionIdsByRoleId[$roleId] = $assignedPermissionIds;
  $roleUserCount[$roleId] = $queryBus->ask(new CountUsersQuery('role_id = :role_id', ['role_id' => $roleId]));
}

$filteredRoles = $roles;
if ($roleSearch !== '') {
  $filteredRoles = array_values(array_filter($roles, function ($role) use ($roleSearch) {
    $haystack = strtolower(($role->name ?? '') . ' ' . ($role->description ?? ''));
    return str_contains($haystack, strtolower($roleSearch));
  }));
}

$filteredPermissions = $permissions;
if ($permissionSearch !== '') {
  $filteredPermissions = array_values(array_filter($filteredPermissions, function ($permission) use ($permissionSearch) {
    $haystack = strtolower(
      ($permission->name ?? '') . ' ' .
      ($permission->description ?? '') . ' ' .
      ($permission->module ?? '') . ' ' .
      ($permission->action ?? '')
    );

    return str_contains($haystack, strtolower($permissionSearch));
  }));
}
if ($permissionModuleFilter !== '') {
  $filteredPermissions = array_values(array_filter($filteredPermissions, function ($permission) use ($permissionModuleFilter) {
    return strtolower((string) ($permission->module ?? '')) === strtolower($permissionModuleFilter);
  }));
}

$permissionModules = [];
foreach ($permissions as $permission) {
  $module = trim((string) ($permission->module ?? ''));
  if ($module !== '') {
    $permissionModules[$module] = true;
  }
}
$permissionModules = array_keys($permissionModules);
sort($permissionModules);

$editUserId = (string) ($_GET['edit_user_id'] ?? '');
$editingUser = null;
if ($canEditUsers && IdValidator::isValid($editUserId)) {
  $editingUser = $queryBus->ask(new GetUserByIdQuery($editUserId));
}

$editRoleId = (string) ($_GET['edit_role_id'] ?? '');
$editingRole = null;
if ($canEditRoles && IdValidator::isValid($editRoleId)) {
  foreach ($roles as $role) {
    if ((string) $role->id === $editRoleId) {
      $editingRole = $role;
      break;
    }
  }
}

$editPermissionId = (string) ($_GET['edit_permission_id'] ?? '');
$editingPermission = null;
if ($canEditPermissions && IdValidator::isValid($editPermissionId)) {
  foreach ($permissions as $permission) {
    if ((string) $permission->id === $editPermissionId) {
      $editingPermission = $permission;
      break;
    }
  }
}

$queryParams = $_GET;
unset($queryParams['status'], $queryParams['message'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/users-list.php', [
  'alerta' => $alerta,
  'loggedUserId' => $loggedUserId,
  'users' => $users,
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
  'editingRole' => $editingRole,
  'editingPermission' => $editingPermission,
  'pagination' => $paginationPages,
  'search' => $search,
  'roleFilter' => $roleFilter,
  'roleSearch' => $roleSearch,
  'permissionSearch' => $permissionSearch,
  'permissionModuleFilter' => $permissionModuleFilter,
  'queryString' => $queryString,
  'canListUsers' => $canListUsers,
  'canCreateUsers' => $canCreateUsers,
  'canEditUsers' => $canEditUsers,
  'canDeleteUsers' => $canDeleteUsers,
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
View::render(VIEW_PATH . '/layout/footer.php');

/**
 * Redirects to the users admin route with a status payload.
 */
function redirectUsers(string $status, string $message): void
{
  $params = [
    'r' => 'users',
    'status' => $status,
    'message' => $message
  ];

  HttpRedirect::to('index.php?' . http_build_query($params));
}

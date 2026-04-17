<?php

/**
 * User edition page controller.
 *
 * Access rules:
 * - authenticated admin only;
 * - requires `user.edit` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Abstractions\CommandBusInterface;
use App\Application\Abstractions\QueryBusInterface;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$loggedUserId = $loggedUser['id'];

if (!RoleManager::isAdmin($loggedUserId)) {
  http_response_code(403);
  die('Acesso restrito ao perfil administrador.');
}

RoleManager::requirePermission($loggedUserId, 'user.edit');

$targetUserId = $_GET['id'] ?? null;
if (!IdValidator::isValid($targetUserId)) {
  header('location: index.php?r=users&status=error');
  exit;
}

$commandBus = AppContainer::commandBus();
$queryBus = AppContainer::queryBus();

$user = $queryBus->ask(new GetUserByIdQuery((string) $targetUserId));
if (!$user) {
  header('location: index.php?r=users&status=error');
  exit;
}

$pageTitle = 'Editar Usuário';
$alerta = null;

$canAssignRole = RoleManager::hasPermission($loggedUserId, 'user.assign_role');
$roles = $canAssignRole ? $queryBus->ask(new ListRolesQuery()) : [];

if (isset($_POST['name'], $_POST['email'])) {
  $isValidCsrfRequest = Csrf::validateFromRequest();
  if (!$isValidCsrfRequest) {
    $alerta = [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível validar a requisição. Tente novamente.'
    ];
  }

  if ($isValidCsrfRequest) {
    $alerta = processUserUpdate($commandBus, $queryBus, $user, $canAssignRole);
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-form.php', [
  'user' => $user,
  'roles' => $roles,
  'canAssignRole' => $canAssignRole,
  'modo' => 'editar',
  'tituloPagina' => $pageTitle,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

/**
 * Validates and persists user edition payload.
 *
 * @return array{tipo:string,icone:string,mensagem:string}|null
 */
function processUserUpdate(
  CommandBusInterface $commandBus,
  QueryBusInterface $queryBus,
  object $user,
  bool $canAssignRole
): ?array {
  try {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    $emailOwner = $queryBus->ask(new GetUserByEmailQuery($email));
    if ($emailOwner && $emailOwner->id !== $user->id) {
      return [
        'tipo' => 'danger',
        'icone' => 'bi bi-exclamation-circle-fill',
        'mensagem' => 'Outro usuário já utiliza esse e-mail.'
      ];
    }

    $roleId = $user->roleId ?? null;
    if ($canAssignRole) {
      $postedRoleId = (string) ($_POST['role_id'] ?? '');
      if ($postedRoleId === '') {
        $roleId = null;
      } elseif (IdValidator::isValid($postedRoleId)) {
        $roleId = $postedRoleId;
      }
    }

    $password = trim((string) ($_POST['password'] ?? ''));
    $passwordForUpdate = $password === '' ? null : $password;

    $updated = $commandBus->dispatch(new UpdateUserCommand(
      (string) ($user->id ?? ''),
      $name,
      $email,
      $passwordForUpdate,
      $roleId
    ));

    if ($updated) {
      header('location: index.php?r=users&status=success');
      exit;
    }

    return [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível atualizar o usuário.'
    ];
  } catch (\Throwable $exception) {
    return [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => $exception->getMessage()
    ];
  }
}

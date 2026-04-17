<?php

/**
 * User creation page controller.
 *
 * Access rules:
 * - authenticated admin only;
 * - requires `user.edit` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Abstractions\CommandBusInterface;
use App\Application\Abstractions\QueryBusInterface;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

if (!RoleManager::isAdmin($userId)) {
  $error = ExceptionHttpMapper::toPayload(new ForbiddenException('Acesso restrito ao perfil administrador.'));
  HttpRedirect::to('index.php?r=vacancies/apply&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

try {
  RoleManager::requirePermission($userId, 'user.edit');
} catch (\Throwable $exception) {
  $error = ExceptionHttpMapper::toPayload($exception);
  HttpRedirect::to('index.php?r=vacancies/apply&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

$commandBus = AppContainer::commandBus();
$queryBus = AppContainer::queryBus();

$pageTitle = 'Cadastrar Usuário';
$alerta = null;

$canAssignRole = RoleManager::hasPermission($userId, 'user.assign_role');
$roles = $canAssignRole ? $queryBus->ask(new ListRolesQuery()) : [];

$user = (object) [
  'name' => '',
  'email' => '',
  'roleId' => null,
];

if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
  $isValidCsrfRequest = Csrf::validateFromRequest();
  if (!$isValidCsrfRequest) {
    $alerta = [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível validar a requisição. Tente novamente.'
    ];
  }

  if ($isValidCsrfRequest) {
    $alerta = processUserCreation($commandBus, $queryBus, $user, $canAssignRole);
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-form.php', [
  'user' => $user,
  'roles' => $roles,
  'canAssignRole' => $canAssignRole,
  'modo' => 'criar',
  'tituloPagina' => $pageTitle,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

/**
 * Validates and persists a new user record.
 *
 * @return array{tipo:string,icone:string,mensagem:string}|null
 */
function processUserCreation(
  CommandBusInterface $commandBus,
  QueryBusInterface $queryBus,
  object $user,
  bool $canAssignRole
): ?array {
  try {
    $user->name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $existingUser = $queryBus->ask(new GetUserByEmailQuery($email));
    if ($existingUser) {
      return [
        'tipo' => 'danger',
        'icone' => 'bi bi-exclamation-circle-fill',
        'mensagem' => 'O Email digitado já está em uso.'
      ];
    }

    $user->email = $email;
    $roleId = null;
    if ($canAssignRole && isset($_POST['role_id']) && IdValidator::isValid((string) $_POST['role_id'])) {
      $roleId = (string) $_POST['role_id'];
      $user->roleId = $roleId;
    }

    $createdUser = $commandBus->dispatch(new CreateUserCommand(
      $user->name,
      $user->email,
      $password,
      $roleId
    ));

    if ($createdUser) {
      HttpRedirect::to('index.php?r=users&status=success');
    }

    return [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível criar o usuário.'
    ];
  } catch (\Throwable $exception) {
    return ExceptionHttpMapper::toAlert($exception);
  }
}

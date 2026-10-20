<?php

/**
 * Standalone user creation form (admin).
 */

use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Security\MessageAuthorizer;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$loggedUser = PageAccess::requireLogin();
PageAccess::requireAdmin($loggedUser);
if (!PageAccess::canAny($loggedUser, ['user.create', 'user.edit'])) {
  throw new ForbiddenException('Você não tem permissão para criar usuários.');
}

$canAssignRole = PageAccess::can($loggedUser, MessageAuthorizer::ASSIGN_ROLE);
$user = (object) ['name' => '', 'email' => '', 'roleId' => null, 'companyId' => null];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $user->name = trim((string) ($_POST['name'] ?? ''));
  $user->email = trim((string) ($_POST['email'] ?? ''));
  $user->roleId = $canAssignRole && ($_POST['role_id'] ?? '') !== '' ? (string) $_POST['role_id'] : null;
  $user->companyId = ($_POST['company_id'] ?? '') !== '' ? (string) $_POST['company_id'] : null;

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new CreateUserCommand(
      $user->name,
      $user->email,
      (string) ($_POST['password'] ?? ''),
      $user->roleId,
      $user->companyId
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('users', 'Usuário criado com sucesso.');
  }
}

$queryBus = AppContainer::queryBus();

Layout::page('/pages/user-form.php', [
  'user' => $user,
  'roles' => $canAssignRole ? $queryBus->ask(new ListRolesQuery()) : [],
  'companies' => $queryBus->ask(new ListCompaniesQuery()),
  'canAssignRole' => $canAssignRole,
  'modo' => 'criar',
  'tituloPagina' => 'Novo usuário',
  'alerta' => $alerta,
]);

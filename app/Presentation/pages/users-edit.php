<?php

/**
 * Standalone user edition form (admin).
 */

use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Security\MessageAuthorizer;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$loggedUser = PageAccess::requireLogin();
PageAccess::requireAdmin($loggedUser);
PageAccess::requirePermission($loggedUser, 'user.edit');

$found = AppContainer::queryBus()->ask(new GetUserByIdQuery((string) ($_GET['id'] ?? '')));
if ($found === null) {
  HttpRedirect::withError('users', new NotFoundException('Usuário não encontrado para edição.'));
}

$canAssignRole = PageAccess::can($loggedUser, MessageAuthorizer::ASSIGN_ROLE);
$user = (object) ['id' => $found->id, 'name' => $found->name, 'email' => $found->email, 'roleId' => $found->roleId];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $user->name = trim((string) ($_POST['name'] ?? ''));
  $user->email = trim((string) ($_POST['email'] ?? ''));
  if ($canAssignRole) {
    $user->roleId = ($_POST['role_id'] ?? '') === '' ? null : (string) $_POST['role_id'];
  }

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $password = (string) ($_POST['password'] ?? '');
    AppContainer::commandBus()->dispatch(new UpdateUserCommand(
      $user->id,
      $user->name,
      $user->email,
      $password === '' ? null : $password,
      $user->roleId,
      $canAssignRole
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('users', 'Usuário atualizado com sucesso.');
  }
}

Layout::page('/pages/user-form.php', [
  'user' => $user,
  'roles' => $canAssignRole ? AppContainer::queryBus()->ask(new ListRolesQuery()) : [],
  'canAssignRole' => $canAssignRole,
  'modo' => 'editar',
  'tituloPagina' => 'Editar Usuário',
  'alerta' => $alerta,
]);

<?php

/**
 * User deletion confirmation (admin).
 */

use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$loggedUser = PageAccess::requireLogin();
PageAccess::requireAdmin($loggedUser);
PageAccess::requirePermission($loggedUser, 'user.delete');

$user = AppContainer::queryBus()->ask(new GetUserByIdQuery((string) ($_GET['id'] ?? '')));
if ($user === null) {
  HttpRedirect::withError('users', new NotFoundException('Usuário não encontrado para exclusão.'));
}

if (isset($_POST['excluir'])) {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new DeleteUserCommand($user->id));
  } catch (\Throwable $exception) {
    HttpRedirect::withError('users', $exception);
  }

  HttpRedirect::withSuccess('users', 'Usuário excluído com sucesso.');
}

Layout::page('/pages/user-delete-confirm.php', ['user' => $user]);

<?php

/**
 * User deletion page controller.
 *
 * Access rules:
 * - authenticated admin only;
 * - requires `user.delete` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Users\GetUserByIdQuery;
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
$loggedUserId = $loggedUser['id'];

if (!RoleManager::isAdmin($loggedUserId)) {
  $error = ExceptionHttpMapper::toPayload(new ForbiddenException('Acesso restrito ao perfil administrador.'));
  HttpRedirect::to('index.php?r=vacancies/apply&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

try {
  RoleManager::requirePermission($loggedUserId, 'user.delete');
} catch (\Throwable $exception) {
  $error = ExceptionHttpMapper::toPayload($exception);
  HttpRedirect::to('index.php?r=vacancies/apply&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

$targetUserId = $_GET['id'] ?? null;
if (!IdValidator::isValid($targetUserId)) {
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Usuário não encontrado para exclusão.'));
  HttpRedirect::to('index.php?r=users&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();

$user = $queryBus->ask(new GetUserByIdQuery((string) $targetUserId));
if (!$user) {
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Usuário não encontrado para exclusão.'));
  HttpRedirect::to('index.php?r=users&status=' . $error['status'] . '&message=' . urlencode($error['message']));
}

if (isset($_POST['excluir'])) {
  if (!Csrf::validateFromRequest()) {
    $error = ExceptionHttpMapper::toPayload(new MessageValidationException(
      'Não foi possível validar a requisição. Tente novamente.'
    ));
    HttpRedirect::to('index.php?r=users&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  }

  try {
    $commandBus->dispatch(new DeleteUserCommand((string) $user->id));
    HttpRedirect::to('index.php?r=users&status=success');
  } catch (\Throwable $exception) {
    $error = ExceptionHttpMapper::toPayload($exception);
    HttpRedirect::to('index.php?r=users&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-delete-confirm.php', [
  'user' => $user
]);
View::render(VIEW_PATH . '/layout/footer.php');

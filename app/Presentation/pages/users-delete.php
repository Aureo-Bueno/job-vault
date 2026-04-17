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

RoleManager::requirePermission($loggedUserId, 'user.delete');

$targetUserId = $_GET['id'] ?? null;
if (!IdValidator::isValid($targetUserId)) {
  header('location: index.php?r=users&status=error');
  exit;
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();

$user = $queryBus->ask(new GetUserByIdQuery((string) $targetUserId));
if (!$user) {
  header('location: index.php?r=users&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  if (!Csrf::validateFromRequest()) {
    header('location: index.php?r=users&status=error');
    exit;
  }

  $commandBus->dispatch(new DeleteUserCommand((string) $user->id));
  header('location: index.php?r=users&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-delete-confirm.php', [
  'user' => $user
]);
View::render(VIEW_PATH . '/layout/footer.php');

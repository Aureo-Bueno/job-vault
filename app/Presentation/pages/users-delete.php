<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;
use App\Util\IdValidator;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$loggedUserId = $loggedUser['id'];

RoleManager::requirePermission($loggedUserId, 'user.delete');

$targetUserId = $_GET['id'] ?? null;
if (!IdValidator::isValid($targetUserId)) {
  header('location: index.php?r=users&status=error');
  exit;
}

$userService = AppContainer::userService();
$user = $userService->getById((string) $targetUserId);
if (!$user) {
  header('location: index.php?r=users&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  $userService->delete((string) $user->id);
  header('location: index.php?r=users&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-delete-confirm.php', [
  'user' => $user
]);
View::render(VIEW_PATH . '/layout/footer.php');

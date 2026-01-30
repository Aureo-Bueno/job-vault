<?php
require BASE_PATH . '/vendor/autoload.php';

use \App\Util\RoleManager;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\IdValidator;

// Require login
$authService = AppContainer::authService();
$authService->requireLogin();
$userId = $authService->getLoggedUser()['id'];

// Check permission to delete
RoleManager::requirePermission($userId, 'vacancy.delete');


$vacancyId = $_GET['id'] ?? null;
if (!IdValidator::isValid($vacancyId)) {
  header('location: index.php?r=home&status=error');
  exit;
}

$vacancyService = AppContainer::vacancyService();
$vacancy = $vacancyService->getById((string) $vacancyId);

if (!$vacancy) {
  header('location: index.php?r=home&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  $vacancyService->delete((string) $vacancy->id);


  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-delete-confirm.php', [
  'vacancy' => $vacancy
]);
View::render(VIEW_PATH . '/layout/footer.php');

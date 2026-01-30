<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Domain\Model\Vacancy;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$pageTitle = 'Cadastrar Vaga';

$authService = AppContainer::authService();
$authService->requireLogin();
$userId = $authService->getLoggedUser()['id'];

RoleManager::requirePermission($userId, 'vacancy.create');

$vacancyService = AppContainer::vacancyService();
$vacancy = new Vacancy();

if (isset($_POST['title'], $_POST['description'], $_POST['is_active'])) {
  $vacancy->title   = $_POST['title'];
  $vacancy->description = $_POST['description'];
  $vacancy->isActive    = $_POST['is_active'];
  $vacancyService->create($vacancy);

  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => $pageTitle
]);
View::render(VIEW_PATH . '/layout/footer.php');

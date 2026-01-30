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

// Check permission to create/edit
RoleManager::requirePermission($userId, 'vacancy.edit');

$pageTitle = 'Editar vaga';

//VALIDA O ID
$vacancyId = $_GET['id'] ?? null;
if (!IdValidator::isValid($vacancyId)) {
  header('location: index.php?r=home&status=error');
  exit;
}

//CONSULTA A VAGA
$vacancyService = AppContainer::vacancyService();
$vacancy = $vacancyService->getById((string) $vacancyId);

// VALIDAR A VAGA
if (!$vacancy) {
  header('location: index.php?r=home&status=error');
  exit;
}




//VALIDAÇAO DO POST
if (isset($_POST['title'], $_POST['description'], $_POST['is_active'])) {

  $vacancy->title = $_POST['title'];
  $vacancy->description = $_POST['description'];
  $vacancy->isActive = $_POST['is_active'];
  $vacancyService->update($vacancy);


  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => $pageTitle
]);
View::render(VIEW_PATH . '/layout/footer.php');

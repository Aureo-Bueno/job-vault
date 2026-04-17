<?php

/**
 * Vacancy creation page controller.
 *
 * Access rules:
 * - authenticated user;
 * - requires `vacancy.create` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\RoleManager;

$pageTitle = 'Cadastrar Vaga';

$authService = AppContainer::authService();
$authService->requireLogin();
$userId = $authService->getLoggedUser()['id'];

RoleManager::requirePermission($userId, 'vacancy.create');

$commandBus = AppContainer::commandBus();
$vacancy = (object) [
  'title' => '',
  'description' => '',
  'isActive' => 's',
];

if (isset($_POST['title'], $_POST['description'], $_POST['is_active'])) {
  if (!Csrf::validateFromRequest()) {
    header('location: index.php?r=home&status=error');
    exit;
  }

  $vacancy->title = (string) $_POST['title'];
  $vacancy->description = (string) $_POST['description'];
  $vacancy->isActive = (string) $_POST['is_active'];

  $created = $commandBus->dispatch(new CreateVacancyCommand(
    $vacancy->title,
    $vacancy->description,
    $vacancy->isActive
  ));

  if (!$created) {
    header('location: index.php?r=home&status=error');
    exit;
  }

  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => $pageTitle
]);
View::render(VIEW_PATH . '/layout/footer.php');

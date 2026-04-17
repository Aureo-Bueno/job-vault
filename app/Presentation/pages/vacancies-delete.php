<?php

/**
 * Vacancy deletion page controller.
 *
 * Access rules:
 * - authenticated user;
 * - requires `vacancy.delete` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$userId = $authService->getLoggedUser()['id'];

RoleManager::requirePermission($userId, 'vacancy.delete');

$vacancyId = $_GET['id'] ?? null;
if (!IdValidator::isValid($vacancyId)) {
  header('location: index.php?r=home&status=error');
  exit;
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();
$vacancy = $queryBus->ask(new GetVacancyByIdQuery((string) $vacancyId));

if (!$vacancy) {
  header('location: index.php?r=home&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  if (!Csrf::validateFromRequest()) {
    header('location: index.php?r=home&status=error');
    exit;
  }

  $deleted = $commandBus->dispatch(new DeleteVacancyCommand((string) $vacancy->id));
  if (!$deleted) {
    header('location: index.php?r=home&status=error');
    exit;
  }

  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-delete-confirm.php', [
  'vacancy' => $vacancy
]);
View::render(VIEW_PATH . '/layout/footer.php');

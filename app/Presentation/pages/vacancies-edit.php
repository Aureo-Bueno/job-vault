<?php

/**
 * Vacancy edition page controller.
 *
 * Access rules:
 * - authenticated user;
 * - requires `vacancy.edit` permission.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$userId = $authService->getLoggedUser()['id'];

RoleManager::requirePermission($userId, 'vacancy.edit');

$pageTitle = 'Editar vaga';

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

if (isset($_POST['title'], $_POST['description'], $_POST['is_active'])) {
  if (!Csrf::validateFromRequest()) {
    header('location: index.php?r=home&status=error');
    exit;
  }

  $updated = $commandBus->dispatch(new UpdateVacancyCommand(
    (string) $vacancy->id,
    (string) $_POST['title'],
    (string) $_POST['description'],
    (string) $_POST['is_active']
  ));

  if (!$updated) {
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

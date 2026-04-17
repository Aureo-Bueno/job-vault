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
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Exceptions\OperationFailedException;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
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
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Vaga não encontrada para exclusão.'));
  HttpRedirect::to('index.php?r=home&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  exit;
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();
$vacancy = $queryBus->ask(new GetVacancyByIdQuery((string) $vacancyId));

if (!$vacancy) {
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Vaga não encontrada para exclusão.'));
  HttpRedirect::to('index.php?r=home&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  exit;
}

if (isset($_POST['excluir'])) {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $deleted = $commandBus->dispatch(new DeleteVacancyCommand((string) $vacancy->id));
    if (!$deleted) {
      throw new OperationFailedException('Não foi possível excluir a vaga.');
    }

    HttpRedirect::to('index.php?r=home&status=success');
  } catch (\Throwable $exception) {
    $error = ExceptionHttpMapper::toPayload($exception);
    HttpRedirect::to(
      'index.php?r=home&status=' . urlencode($error['status']) .
      '&message=' . urlencode($error['message'])
    );
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-delete-confirm.php', [
  'vacancy' => $vacancy
]);
View::render(VIEW_PATH . '/layout/footer.php');

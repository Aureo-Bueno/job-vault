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

RoleManager::requirePermission($userId, 'vacancy.edit');

$pageTitle = 'Editar vaga';

$vacancyId = $_GET['id'] ?? null;
if (!IdValidator::isValid($vacancyId)) {
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Vaga não encontrada para edição.'));
  HttpRedirect::to('index.php?r=home&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  exit;
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();
$vacancy = $queryBus->ask(new GetVacancyByIdQuery((string) $vacancyId));

if (!$vacancy) {
  $error = ExceptionHttpMapper::toPayload(new NotFoundException('Vaga não encontrada para edição.'));
  HttpRedirect::to('index.php?r=home&status=' . $error['status'] . '&message=' . urlencode($error['message']));
  exit;
}

if (isset($_POST['title'], $_POST['description'], $_POST['is_active'])) {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $updated = $commandBus->dispatch(new UpdateVacancyCommand(
      (string) $vacancy->id,
      (string) $_POST['title'],
      (string) $_POST['description'],
      (string) $_POST['is_active']
    ));

    if (!$updated) {
      throw new OperationFailedException('Não foi possível atualizar a vaga.');
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
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => $pageTitle
]);
View::render(VIEW_PATH . '/layout/footer.php');

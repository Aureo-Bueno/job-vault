<?php

/**
 * Vacancy application page controller for end users.
 *
 * Responsibilities:
 * - accepts application submissions with CSRF validation;
 * - validates vacancy identity and active status;
 * - renders only active vacancies and application status feedback.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Applications\ListAppliedVacancyIdsByUserQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Queries\Vacancies\ListVacanciesQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\StatusAlertMapper;
use App\Presentation\View;
use App\Util\Csrf;
use App\Util\IdValidator;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $vacancyId = filter_input(INPUT_POST, 'vacancy_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (!IdValidator::isValid($vacancyId)) {
      throw new MessageValidationException('Identificador da vaga inválido.');
    }

    $vacancy = $queryBus->ask(new GetVacancyByIdQuery((string) $vacancyId));
    if (!$vacancy || ($vacancy->isActive ?? 'n') !== 's') {
      throw new NotFoundException('A vaga informada não está disponível para candidatura.');
    }

    $result = $commandBus->dispatch(new ApplyToVacancyCommand($userId, (string) $vacancyId));
    $status = is_object($result) && isset($result->status) ? (string) $result->status : 'error';

    HttpRedirect::to('index.php?r=vacancies/apply&status=' . urlencode($status));
  } catch (\Throwable $exception) {
    $error = ExceptionHttpMapper::toPayload($exception);
    HttpRedirect::to(
      'index.php?r=vacancies/apply&status=' . urlencode($error['status']) .
      '&message=' . urlencode($error['message'])
    );
  }
}

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$alerta = StatusAlertMapper::from($status);
$customMessage = trim((string) ($_GET['message'] ?? ''));
if ($alerta && $customMessage !== '') {
  $alerta['mensagem'] = $customMessage;
}

$vacancies = $queryBus->ask(new ListVacanciesQuery("is_active = 's'", 'created_at DESC'));
$applied = $queryBus->ask(new ListAppliedVacancyIdsByUserQuery((string) $userId));
$appliedMap = array_fill_keys($applied, true);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancies-apply-list.php', [
  'alerta' => $alerta,
  'vacancies' => $vacancies,
  'appliedMap' => $appliedMap
]);
View::render(VIEW_PATH . '/layout/footer.php');

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
use App\Application\Queries\Applications\ListAppliedVacancyIdsByUserQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Queries\Vacancies\ListVacanciesQuery;
use App\Infrastructure\Container\AppContainer;
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
  if (!Csrf::validateFromRequest()) {
    header('Location: index.php?r=vacancies/apply&status=error');
    exit;
  }

  $vacancyId = filter_input(INPUT_POST, 'vacancy_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  if (!IdValidator::isValid($vacancyId)) {
    header('Location: index.php?r=vacancies/apply&status=error');
    exit;
  }

  $vacancy = $queryBus->ask(new GetVacancyByIdQuery((string) $vacancyId));
  if (!$vacancy || ($vacancy->isActive ?? 'n') !== 's') {
    header('Location: index.php?r=vacancies/apply&status=error');
    exit;
  }

  $result = $commandBus->dispatch(new ApplyToVacancyCommand($userId, (string) $vacancyId));
  $status = is_object($result) && isset($result->status) ? (string) $result->status : 'error';

  header('Location: index.php?r=vacancies/apply&status=' . $status);
  exit;
}

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$statusAlerts = [
  'success' => [
    'tipo' => 'success',
    'icone' => 'bi bi-check-circle-fill',
    'mensagem' => 'Candidatura enviada com sucesso!'
  ],
  'exists' => [
    'tipo' => 'info',
    'icone' => 'bi bi-info-circle-fill',
    'mensagem' => 'Você já se candidatou a esta vaga.'
  ],
  'error' => [
    'tipo' => 'danger',
    'icone' => 'bi bi-exclamation-circle-fill',
    'mensagem' => 'Não foi possível enviar sua candidatura.'
  ],
];
$alerta = $statusAlerts[$status] ?? null;

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

<?php

require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\IdValidator;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

$vacancyService = AppContainer::vacancyService();
$applicationService = AppContainer::applicationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vacancyId = filter_input(INPUT_POST, 'vacancy_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  if (!IdValidator::isValid($vacancyId)) {
    header('Location: index.php?r=vacancies/apply&status=error');
    exit;
  }

  $vacancy = $vacancyService->getById((string) $vacancyId);
  if (!$vacancy || ($vacancy->isActive ?? 'n') !== 's') {
    header('Location: index.php?r=vacancies/apply&status=error');
    exit;
  }

  $result = $applicationService->apply($userId, (string) $vacancyId);

  header('Location: index.php?r=vacancies/apply&status=' . $result);
  exit;
}

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$alerta = null;
if ($status === 'success') {
  $alerta = [
    'tipo' => 'success',
    'icone' => 'bi bi-check-circle-fill',
    'mensagem' => 'Candidatura enviada com sucesso!'
  ];
} elseif ($status === 'exists') {
  $alerta = [
    'tipo' => 'info',
    'icone' => 'bi bi-info-circle-fill',
    'mensagem' => 'Você já se candidatou a esta vaga.'
  ];
} elseif ($status === 'error') {
  $alerta = [
    'tipo' => 'danger',
    'icone' => 'bi bi-exclamation-circle-fill',
    'mensagem' => 'Não foi possível enviar sua candidatura.'
  ];
}

$vacancies = $vacancyService->list("is_active = 's'", 'created_at DESC');
$applied = $applicationService->getAppliedVacancyIdsByUser($userId);
$appliedMap = array_fill_keys($applied, true);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancies-apply-list.php', [
  'alerta' => $alerta,
  'vacancies' => $vacancies,
  'appliedMap' => $appliedMap
]);
View::render(VIEW_PATH . '/layout/footer.php');

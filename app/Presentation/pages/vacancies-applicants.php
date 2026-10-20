<?php

/**
 * Hiring pipeline (Kanban) of a vacancy: applicants grouped by stage, ranked by match.
 * POST: move an application (advance / reject / reopen) or add an internal note.
 */

use App\Application\Commands\Applications\AddApplicationNoteCommand;
use App\Application\Commands\Applications\MoveApplicationCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Applications\GetVacancyPipelineQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\StatusAlertMapper;

$user = PageAccess::requireLogin();
if (!PageAccess::can($user, PageAccess::MANAGE_VACANCIES)) {
  HttpRedirect::route('vacancies/apply');
}

$vacancyId = (string) ($_GET['id'] ?? '');
$back = ['id' => $vacancyId];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = (string) ($_POST['action'] ?? '');
  $applicationId = (string) ($_POST['application_id'] ?? '');

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $commandBus = AppContainer::commandBus();
    if ($action === 'note') {
      $commandBus->dispatch(new AddApplicationNoteCommand($applicationId, (string) ($_POST['body'] ?? '')));
      $message = 'Nota adicionada.';
    } elseif ($action === 'move') {
      $move = (string) ($_POST['move'] ?? '');
      $result = $commandBus->dispatch(new MoveApplicationCommand(
        $applicationId,
        $move,
        (string) ($_POST['expected_status'] ?? ''),
        (string) ($_POST['feedback'] ?? '')
      ));
      $message = match (true) {
        $result->vacancyClosedAsFilled => 'Candidato aprovado. Todas as posições foram preenchidas e a vaga foi encerrada.',
        $move === MoveApplicationCommand::REJECT => 'Candidatura reprovada.',
        $move === MoveApplicationCommand::REOPEN => 'Candidatura reaberta para análise.',
        default => 'Candidato movido para ' . $result->statusLabel . '.',
      };
    } else {
      throw new MessageValidationException('Ação desconhecida.');
    }
  } catch (\Throwable $exception) {
    HttpRedirect::withError('vacancies/applicants', $exception, $back);
  }

  HttpRedirect::withSuccess('vacancies/applicants', $message, $back);
}

$queryBus = AppContainer::queryBus();

try {
  $vacancy = $queryBus->ask(new GetVacancyByIdQuery($vacancyId));
  if ($vacancy === null) {
    HttpRedirect::withError('home', new NotFoundException('Vaga não encontrada.'));
  }

  $pipeline = $queryBus->ask(new GetVacancyPipelineQuery($vacancy->id));
} catch (\Throwable $exception) {
  HttpRedirect::withError('home', $exception);
}

Layout::page('/pages/vacancy-applicants.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'vacancy' => $vacancy,
  'pipeline' => $pipeline,
]);

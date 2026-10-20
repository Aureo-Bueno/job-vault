<?php

/**
 * "Minhas candidaturas": the candidate follows each application, withdraws or applies again.
 */

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Applications\WithdrawApplicationCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Applications\ListMyApplicationsQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\StatusAlertMapper;

PageAccess::requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = (string) ($_POST['action'] ?? '');

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $commandBus = AppContainer::commandBus();
    if ($action === 'withdraw') {
      $commandBus->dispatch(new WithdrawApplicationCommand((string) ($_POST['application_id'] ?? '')));
      $message = 'Você desistiu da candidatura. Enquanto a vaga estiver aberta, é possível se candidatar novamente.';
    } elseif ($action === 'reapply') {
      $commandBus->dispatch(new ApplyToVacancyCommand((string) ($_POST['vacancy_id'] ?? '')));
      $message = 'Candidatura enviada novamente!';
    } else {
      throw new MessageValidationException('Ação desconhecida.');
    }
  } catch (\Throwable $exception) {
    HttpRedirect::withError('applications', $exception);
  }

  HttpRedirect::withSuccess('applications', $message);
}

Layout::page('/pages/my-applications.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'applications' => AppContainer::queryBus()->ask(new ListMyApplicationsQuery()),
]);

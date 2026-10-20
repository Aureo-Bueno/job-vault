<?php

/**
 * Open vacancies for candidates. The "only open vacancies, once per user" rules live in the domain.
 */

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Applications\ListMyAppliedVacancyIdsQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\StatusAlertMapper;

PageAccess::requireLogin();
$queryBus = AppContainer::queryBus();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new ApplyToVacancyCommand((string) ($_POST['vacancy_id'] ?? '')));
  } catch (\Throwable $exception) {
    HttpRedirect::withError('vacancies/apply', $exception);
  }

  HttpRedirect::withSuccess('vacancies/apply', 'Candidatura enviada com sucesso.');
}

Layout::page('/pages/vacancies-apply-list.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'vacancies' => $queryBus->ask(new ListOpenVacanciesQuery()),
  'appliedMap' => array_fill_keys($queryBus->ask(new ListMyAppliedVacancyIdsQuery()), true),
]);

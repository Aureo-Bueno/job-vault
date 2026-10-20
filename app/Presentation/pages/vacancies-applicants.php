<?php

/**
 * Applicants list for a vacancy, ordered by match score.
 */

use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Applications\ListVacancyApplicantsQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$user = PageAccess::requireLogin();
if (!PageAccess::can($user, PageAccess::MANAGE_VACANCIES)) {
  HttpRedirect::route('vacancies/apply');
}

$vacancyId = (string) ($_GET['id'] ?? '');
$queryBus = AppContainer::queryBus();

try {
  $vacancy = $queryBus->ask(new GetVacancyByIdQuery($vacancyId));
  if ($vacancy === null) {
    HttpRedirect::withError('home', new NotFoundException('Vaga não encontrada.'));
  }

  $applicants = $queryBus->ask(new ListVacancyApplicantsQuery($vacancy->id));
} catch (\Throwable $exception) {
  HttpRedirect::withError('home', $exception);
}

Layout::page('/pages/vacancy-applicants.php', [
  'vacancy' => $vacancy,
  'applicants' => $applicants,
]);

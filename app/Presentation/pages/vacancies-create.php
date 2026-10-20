<?php

/**
 * Vacancy creation form.
 */

use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\VacancyForm;

$user = PageAccess::requireLogin();
PageAccess::requirePermission($user, 'vacancy.create');

$queryBus = AppContainer::queryBus();
$isAdmin = PageAccess::isAdmin($user);
$companies = $isAdmin ? $queryBus->ask(new ListCompaniesQuery()) : [];
$myCompany = !$isAdmin ? $queryBus->ask(new GetMyCompanyQuery()) : null;

$vacancy = (object) [
  'title' => '',
  'description' => '',
  'status' => 'aberta',
  'currentStatus' => '',
  'positions' => 1,
  'area' => 'backend',
  'seniority' => 'pleno',
  'workMode' => 'remoto',
  'topics' => [],
  'contractType' => 'clt',
  'salaryMin' => '',
  'salaryMax' => '',
  'city' => '',
  'state' => '',
  'benefits' => [],
  'closesAt' => '',
  'companyId' => $myCompany?->id ?? ($companies[0]->id ?? ''),
];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  VacancyForm::fillFromPost($vacancy, $_POST);
  if ($isAdmin) {
    $vacancy->companyId = trim((string) ($_POST['company_id'] ?? ''));
  }

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new CreateVacancyCommand(
      $vacancy->title,
      $vacancy->description,
      $vacancy->status,
      $vacancy->area,
      $vacancy->seniority,
      $vacancy->workMode,
      $vacancy->rawTopics,
      $isAdmin && $vacancy->companyId !== '' ? $vacancy->companyId : null,
      $vacancy->positions,
      $vacancy->contractType,
      VacancyForm::salary($vacancy->salaryMin),
      VacancyForm::salary($vacancy->salaryMax),
      $vacancy->city,
      $vacancy->state,
      $vacancy->rawBenefits,
      $vacancy->closesAt !== '' ? $vacancy->closesAt : null
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', $vacancy->status === 'rascunho'
      ? 'Rascunho salvo. Publique quando estiver pronto.'
      : 'Vaga publicada com sucesso.');
  }
}

Layout::page('/pages/vacancy-form.php', VacancyForm::options() + [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Nova vaga',
  'alerta' => $alerta,
  'isAdmin' => $isAdmin,
  'companies' => $companies,
  'myCompany' => $myCompany,
]);

<?php

/**
 * Vacancy edition form.
 */

use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\VacancyForm;

$user = PageAccess::requireLogin();
PageAccess::requirePermission($user, 'vacancy.edit');

$queryBus = AppContainer::queryBus();
$found = null;
try {
  $found = $queryBus->ask(new GetVacancyByIdQuery((string) ($_GET['id'] ?? '')));
} catch (\Throwable $exception) {
  HttpRedirect::withError('home', $exception);
}

if ($found === null) {
  HttpRedirect::withError('home', new NotFoundException('Vaga não encontrada para edição.'));
}

$isAdmin = PageAccess::isAdmin($user);
$companies = $isAdmin ? $queryBus->ask(new ListCompaniesQuery()) : [];
$myCompany = !$isAdmin ? $queryBus->ask(new GetMyCompanyQuery()) : null;

$vacancy = (object) [
  'id' => $found->id,
  'title' => $found->title,
  'description' => $found->description,
  'status' => $found->status,
  'currentStatus' => $found->status,
  'closeReasonLabel' => $found->closeReasonLabel,
  'positions' => $found->positions,
  'area' => $found->area,
  'seniority' => $found->seniority,
  'workMode' => $found->workMode,
  'topics' => $found->topics,
  'contractType' => $found->contractType,
  'salaryMin' => $found->salaryMin !== null ? (string) $found->salaryMin : '',
  'salaryMax' => $found->salaryMax !== null ? (string) $found->salaryMax : '',
  'city' => $found->city,
  'state' => $found->state,
  'benefits' => $found->benefits,
  'closesAt' => $found->closesAt,
  'companyId' => $found->companyId ?? '',
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

    AppContainer::commandBus()->dispatch(new UpdateVacancyCommand(
      $vacancy->id,
      $vacancy->title,
      $vacancy->description,
      $vacancy->status,
      $vacancy->area,
      $vacancy->seniority,
      $vacancy->workMode,
      $vacancy->rawTopics,
      $isAdmin && $vacancy->companyId !== '' ? $vacancy->companyId : null,
      $isAdmin,
      $vacancy->positions,
      $vacancy->contractType,
      VacancyForm::salary($vacancy->salaryMin),
      VacancyForm::salary($vacancy->salaryMax),
      $vacancy->city,
      $vacancy->state,
      $vacancy->rawBenefits,
      $vacancy->closesAt
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', match (true) {
      $found->status === 'rascunho' && $vacancy->status === 'aberta' => 'Vaga publicada com sucesso.',
      $found->status !== 'aberta' && $vacancy->status === 'aberta' => 'Vaga reaberta: voltou a aceitar candidaturas.',
      $found->status === 'aberta' && $vacancy->status === 'encerrada' => 'Vaga encerrada.',
      default => 'Vaga atualizada com sucesso.',
    });
  }
}

Layout::page('/pages/vacancy-form.php', VacancyForm::options() + [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Editar vaga',
  'alerta' => $alerta,
  'isAdmin' => $isAdmin,
  'companies' => $companies,
  'myCompany' => $myCompany,
]);

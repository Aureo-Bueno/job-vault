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
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

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
  'isActive' => $found->isActive,
  'area' => $found->area,
  'seniority' => $found->seniority,
  'workMode' => $found->workMode,
  'topics' => $found->topics,
  'companyId' => $found->companyId ?? '',
];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $vacancy->title = (string) ($_POST['title'] ?? '');
  $vacancy->description = (string) ($_POST['description'] ?? '');
  $vacancy->isActive = ($_POST['vacancy_status'] ?? 'active') === 'active';
  $vacancy->area = (string) ($_POST['area'] ?? 'backend');
  $vacancy->seniority = (string) ($_POST['seniority'] ?? 'pleno');
  $vacancy->workMode = (string) ($_POST['work_mode'] ?? 'remoto');
  $rawTopics = (string) ($_POST['topics'] ?? '');
  $vacancy->topics = array_values(array_filter(array_map('trim', explode(',', $rawTopics)), static fn (string $t): bool => $t !== ''));
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
      $vacancy->isActive,
      $vacancy->area,
      $vacancy->seniority,
      $vacancy->workMode,
      $rawTopics,
      $isAdmin && $vacancy->companyId !== '' ? $vacancy->companyId : null,
      $isAdmin
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', 'Vaga atualizada com sucesso.');
  }
}

Layout::page('/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Editar vaga',
  'alerta' => $alerta,
  'areas' => WorkArea::options(),
  'seniorities' => Seniority::options(),
  'workModes' => WorkMode::options(),
  'popularTopics' => WorkArea::popularTopics(),
  'isAdmin' => $isAdmin,
  'companies' => $companies,
  'myCompany' => $myCompany,
]);

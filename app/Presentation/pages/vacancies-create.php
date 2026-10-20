<?php

/**
 * Vacancy creation form.
 */

use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Companies\ListCompaniesQuery;
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
PageAccess::requirePermission($user, 'vacancy.create');

$queryBus = AppContainer::queryBus();
$isAdmin = PageAccess::isAdmin($user);
$companies = $isAdmin ? $queryBus->ask(new ListCompaniesQuery()) : [];
$myCompany = !$isAdmin ? $queryBus->ask(new GetMyCompanyQuery()) : null;

$vacancy = (object) [
  'title' => '',
  'description' => '',
  'isActive' => true,
  'area' => 'backend',
  'seniority' => 'pleno',
  'workMode' => 'remoto',
  'topics' => [],
  'companyId' => $myCompany?->id ?? ($companies[0]->id ?? ''),
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

    AppContainer::commandBus()->dispatch(new CreateVacancyCommand(
      $vacancy->title,
      $vacancy->description,
      $vacancy->isActive,
      $vacancy->area,
      $vacancy->seniority,
      $vacancy->workMode,
      $rawTopics,
      $isAdmin && $vacancy->companyId !== '' ? $vacancy->companyId : null
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', 'Vaga cadastrada com sucesso.');
  }
}

Layout::page('/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Nova vaga',
  'alerta' => $alerta,
  'areas' => WorkArea::options(),
  'seniorities' => Seniority::options(),
  'workModes' => WorkMode::options(),
  'popularTopics' => WorkArea::popularTopics(),
  'isAdmin' => $isAdmin,
  'companies' => $companies,
  'myCompany' => $myCompany,
]);

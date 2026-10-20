<?php

/**
 * Open vacancies for candidates with compatibility (match) score and filters.
 */

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Applications\ListMyAppliedVacancyIdsQuery;
use App\Application\Queries\Profile\GetMyCandidateProfileQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Domain\Shared\BrazilianState;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\VacancyDeadline;
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

  HttpRedirect::withSuccess('vacancies/apply', 'Candidatura enviada com sucesso! A empresa já pode avaliar seu perfil.');
}

$search = trim((string) ($_GET['search'] ?? ''));
$areaFilter = trim((string) ($_GET['area'] ?? ''));
$workModeFilter = trim((string) ($_GET['work_mode'] ?? ''));
$salaryFilter = (int) ($_GET['min_salary'] ?? 0);
$salaryOptions = [3000, 5000, 8000, 12000, 15000];
$salaryFilter = in_array($salaryFilter, $salaryOptions, true) ? $salaryFilter : 0;
$stateFilter = strtoupper(trim((string) ($_GET['state'] ?? '')));
$contractFilter = trim((string) ($_GET['contract_type'] ?? ''));
$sort = in_array($_GET['sort'] ?? '', ['relevance', 'match', 'recent'], true) ? (string) $_GET['sort'] : 'relevance';

$profile = $queryBus->ask(new GetMyCandidateProfileQuery());
$vacancies = $queryBus->ask(new ListOpenVacanciesQuery(
  $search,
  $areaFilter,
  $workModeFilter,
  $sort,
  $salaryFilter > 0 ? $salaryFilter : null,
  $stateFilter,
  $contractFilter
));

Layout::page('/pages/vacancies-apply-list.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'profile' => $profile,
  'vacancies' => $vacancies,
  'appliedMap' => array_fill_keys($queryBus->ask(new ListMyAppliedVacancyIdsQuery()), true),
  'areas' => WorkArea::options(),
  'workModes' => WorkMode::options(),
  'search' => $search,
  'areaFilter' => $areaFilter,
  'workModeFilter' => $workModeFilter,
  'contractTypes' => ContractType::options(),
  'states' => BrazilianState::options(),
  'salaryOptions' => $salaryOptions,
  'salaryFilter' => $salaryFilter,
  'stateFilter' => $stateFilter,
  'contractFilter' => $contractFilter,
  'today' => VacancyDeadline::today(),
  'sort' => $sort,
]);

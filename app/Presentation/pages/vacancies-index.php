<?php

/**
 * Vacancy back-office (users with `vacancy.manage`): search, area/status filter and pagination.
 */

use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use App\Domain\Shared\WorkArea;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\Pagination;
use App\Presentation\Support\StatusAlertMapper;

$user = PageAccess::requireLogin();
if (!PageAccess::can($user, PageAccess::MANAGE_VACANCIES)) {
  HttpRedirect::route('vacancies/apply');
}

$queryBus = AppContainer::queryBus();
$isAdmin = PageAccess::isAdmin($user);
$myCompany = !$isAdmin ? $queryBus->ask(new GetMyCompanyQuery()) : null;

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = in_array($_GET['status_filter'] ?? '', ['rascunho', 'aberta', 'encerrada'], true) ? $_GET['status_filter'] : '';
$areaFilter = trim((string) ($_GET['area_filter'] ?? ''));

$result = $queryBus->ask(new SearchVacanciesQuery(
  $search,
  $statusFilter === '' ? null : $statusFilter,
  Pagination::currentPage(),
  5,
  $areaFilter !== '' ? $areaFilter : null
));

Layout::page('/pages/vacancies-list.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'vacancies' => $result->items,
  'canEdit' => PageAccess::can($user, 'vacancy.edit'),
  'canDelete' => PageAccess::can($user, 'vacancy.delete'),
  'canCreate' => PageAccess::can($user, 'vacancy.create'),
  'isAdmin' => $isAdmin,
  'myCompany' => $myCompany,
  'areas' => WorkArea::options(),
  'areaFilter' => $areaFilter,
  'pagination' => Pagination::links($result),
  'total' => $result->total,
  'search' => $search,
  'statusFilter' => $statusFilter,
  'queryString' => Pagination::preservedQuery(),
]);

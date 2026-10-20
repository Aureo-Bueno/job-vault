<?php

/**
 * Vacancy back-office (users with `vacancy.manage`): search, status filter and pagination.
 */

use App\Application\Queries\Vacancies\SearchVacanciesQuery;
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

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = in_array($_GET['status_filter'] ?? '', ['active', 'inactive'], true) ? $_GET['status_filter'] : '';

$result = AppContainer::queryBus()->ask(new SearchVacanciesQuery(
  $search,
  $statusFilter === '' ? null : $statusFilter === 'active',
  Pagination::currentPage(),
  5
));

Layout::page('/pages/vacancies-list.php', [
  'alerta' => StatusAlertMapper::fromRequest(),
  'vacancies' => $result->items,
  'canEdit' => PageAccess::can($user, 'vacancy.edit'),
  'canDelete' => PageAccess::can($user, 'vacancy.delete'),
  'canCreate' => PageAccess::can($user, 'vacancy.create'),
  'pagination' => Pagination::links($result),
  'search' => $search,
  'statusFilter' => $statusFilter,
  'queryString' => Pagination::preservedQuery(),
]);

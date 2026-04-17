<?php

/**
 * Vacancies listing page controller for administrative users.
 *
 * Responsibilities:
 * - restrict route to admin/manager;
 * - enforce `vacancy.view` permission;
 * - apply search, status filter and pagination.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Application\Queries\Vacancies\CountVacanciesQuery;
use App\Application\Queries\Vacancies\ListVacanciesQuery;
use App\Db\Pagination;
use App\Domain\ValueObject\SearchTerm;
use App\Infrastructure\Container\AppContainer;
use App\Infrastructure\Persistence\SqlCriteria;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\StatusAlertMapper;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();

$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

$isAdmin = RoleManager::isAdmin($userId);
$isManager = RoleManager::isManager($userId);
if (!$isAdmin && !$isManager) {
  HttpRedirect::to('index.php?r=vacancies/apply');
}

RoleManager::requirePermission($userId, 'vacancy.view');

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status_filter'] ?? ''));
$statusFilter = in_array($statusFilter, ['s', 'n'], true) ? $statusFilter : '';

$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$alerta = StatusAlertMapper::from($status);
$customMessage = trim((string) ($_GET['message'] ?? ''));
if ($alerta && $customMessage !== '') {
  $alerta['mensagem'] = $customMessage;
}

$criteria = new SqlCriteria();
$searchTerm = SearchTerm::fromString($search);
if ($searchTerm) {
  $criteria->addContainsAny(['title'], $searchTerm, 'search_vacancy');
}
if ($statusFilter !== '') {
  $criteria->addEquals('is_active', 'status_filter', $statusFilter);
}

$where = $criteria->whereClause();
$parameters = $criteria->parameters();

$queryBus = AppContainer::queryBus();
$totalVacancies = $queryBus->ask(new CountVacanciesQuery($where, $parameters));

$currentPage = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$pagination = new Pagination((int) $totalVacancies, $currentPage, 5);
$paginationPages = $pagination->getPages();

$vacancies = $queryBus->ask(new ListVacanciesQuery(
  $where,
  'created_at DESC',
  $pagination->getLimit(),
  $parameters
));

$canEdit = RoleManager::hasPermission($userId, 'vacancy.edit');
$canDelete = RoleManager::hasPermission($userId, 'vacancy.delete');
$canCreate = RoleManager::hasPermission($userId, 'vacancy.create');

$queryParams = $_GET;
unset($queryParams['status'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancies-list.php', [
  'alerta' => $alerta,
  'vacancies' => $vacancies,
  'canEdit' => $canEdit,
  'canDelete' => $canDelete,
  'canCreate' => $canCreate,
  'pagination' => $paginationPages,
  'search' => $search,
  'statusFilter' => $statusFilter,
  'queryString' => $queryString
]);
View::render(VIEW_PATH . '/layout/footer.php');

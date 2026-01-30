<?php

require BASE_PATH . '/vendor/autoload.php';


use App\Infrastructure\Container\AppContainer;
use \App\Db\Pagination;
use \App\Util\RoleManager;
use App\Presentation\View;

// Require user to be logged in
$authService = AppContainer::authService();
$authService->requireLogin();

// Get logged-in user info
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

// Only admin/manager can access this list
$isAdmin = RoleManager::isAdmin($userId);
$isManager = RoleManager::isManager($userId);
if (!$isAdmin && !$isManager) {
  header('Location: index.php?r=vacancies/apply');
  exit;
}

// Check if user can view vagas
RoleManager::requirePermission($userId, 'vacancy.view');

// Get search input
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$statusFilter = filter_input(INPUT_GET, 'status_filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$statusFilter = in_array($statusFilter, ['s', 'n']) ? $statusFilter : '';

// Alerts
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$alerta = null;
if ($status === 'success') {
  $alerta = [
    'tipo' => 'success',
    'icone' => 'bi bi-check-circle-fill',
    'mensagem' => 'Ação executada com sucesso!'
  ];
} elseif ($status === 'error') {
  $alerta = [
    'tipo' => 'danger',
    'icone' => 'bi bi-exclamation-circle-fill',
    'mensagem' => 'Não foi possível executar ação!'
  ];
}

// Build WHERE conditions
$conditions = [];
if (!empty($search)) {
  $searchTerm = str_replace(' ', '%', addslashes($search));
  $conditions[] = "title LIKE '%{$searchTerm}%'";
}
if (!empty($statusFilter)) {
  $conditions[] = "is_active = '{$statusFilter}'";
}

$where = !empty($conditions) ? implode(' AND ', $conditions) : null;

// Get total vacancies
$vacancyService = AppContainer::vacancyService();
$totalVacancies = $vacancyService->count($where);

// Pagination
$currentPage = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$pagination = new Pagination($totalVacancies, $currentPage, 5);
$paginationPages = $pagination->getPages();

// Get vacancies
$vacancies = $vacancyService->list($where, 'created_at DESC', $pagination->getLimit());

// Check user permissions for template
$canEdit = RoleManager::hasPermission($userId, 'vacancy.edit');
$canDelete = RoleManager::hasPermission($userId, 'vacancy.delete');
$canCreate = RoleManager::hasPermission($userId, 'vacancy.create');

// Pagination query (preserve filters)
$queryParams = $_GET;
unset($queryParams['status'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

// Load templates
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

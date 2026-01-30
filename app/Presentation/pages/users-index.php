<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Db\Pagination;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

RoleManager::requirePermission($userId, 'user.list');

$userService = AppContainer::userService();
$roleService = AppContainer::roleService();

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

$conditions = [];
if (!empty($search)) {
  $searchTerm = str_replace(' ', '%', addslashes($search));
  $conditions[] = "(name LIKE '%{$searchTerm}%' OR email LIKE '%{$searchTerm}%')";
}

$where = !empty($conditions) ? implode(' AND ', $conditions) : null;

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

$totalUsers = $userService->count($where);
$currentPage = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$pagination = new Pagination($totalUsers, $currentPage, 8);
$paginationPages = $pagination->getPages();

$users = $userService->list($where, 'id DESC', $pagination->getLimit());

$roles = $roleService->listRoles();
$rolesById = [];
foreach ($roles as $role) {
  $rolesById[$role->id] = $role->name;
}

$canEdit = RoleManager::hasPermission($userId, 'user.edit');
$canDelete = RoleManager::hasPermission($userId, 'user.delete');
$canCreate = $canEdit;

$queryParams = $_GET;
unset($queryParams['status'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/users-list.php', [
  'alerta' => $alerta,
  'users' => $users,
  'rolesById' => $rolesById,
  'canEdit' => $canEdit,
  'canDelete' => $canDelete,
  'canCreate' => $canCreate,
  'pagination' => $paginationPages,
  'search' => $search,
  'queryString' => $queryString
]);
View::render(VIEW_PATH . '/layout/footer.php');

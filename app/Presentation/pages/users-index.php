<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Db\Pagination;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioLogado = $authService->getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

RoleManager::requirePermission($usuarioId, 'usuario.listar');

$usuarioService = AppContainer::usuarioService();
$roleService = AppContainer::roleService();

$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

$condicoes = [];
if (!empty($busca)) {
  $searchTerm = str_replace(' ', '%', addslashes($busca));
  $condicoes[] = "(nome LIKE '%{$searchTerm}%' OR email LIKE '%{$searchTerm}%')";
}

$where = !empty($condicoes) ? implode(' AND ', $condicoes) : null;

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

$totalUsuarios = $usuarioService->count($where);
$paginaAtual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$obPagination = new Pagination($totalUsuarios, $paginaAtual, 8);
$paginacao = $obPagination->getPages();

$usuarios = $usuarioService->list($where, 'id DESC', $obPagination->getLimit());

$roles = $roleService->listRoles();
$rolesById = [];
foreach ($roles as $role) {
  $rolesById[$role->id] = $role->nome;
}

$podeEditar = RoleManager::hasPermission($usuarioId, 'usuario.editar');
$podeDeletar = RoleManager::hasPermission($usuarioId, 'usuario.deletar');
$podeCriar = $podeEditar;

$queryParams = $_GET;
unset($queryParams['status'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/users-list.php', [
  'alerta' => $alerta,
  'usuarios' => $usuarios,
  'rolesById' => $rolesById,
  'podeEditar' => $podeEditar,
  'podeDeletar' => $podeDeletar,
  'podeCriar' => $podeCriar,
  'paginacao' => $paginacao,
  'busca' => $busca,
  'queryString' => $queryString
]);
View::render(VIEW_PATH . '/layout/footer.php');

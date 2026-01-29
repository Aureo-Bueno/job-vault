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
$usuarioLogado = $authService->getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

// Check if user can view vagas
RoleManager::requirePermission($usuarioId, 'vaga.visualizar');

// Get search input
$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filtroStatus = filter_input(INPUT_GET, 'filtroStatus', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filtroStatus = in_array($filtroStatus, ['s', 'n']) ? $filtroStatus : '';

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
$condicoes = [];
if (!empty($busca)) {
  $searchTerm = str_replace(' ', '%', addslashes($busca));
  $condicoes[] = "titulo LIKE '%{$searchTerm}%'";
}
if (!empty($filtroStatus)) {
  $condicoes[] = "ativo = '{$filtroStatus}'";
}

$where = !empty($condicoes) ? implode(' AND ', $condicoes) : null;

// Get total vacancies
$vagaService = AppContainer::vagaService();
$quantidadeVagas = $vagaService->count($where);

// Pagination
$paginaAtual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$obPagination = new Pagination($quantidadeVagas, $paginaAtual, 5);
$paginacao = $obPagination->getPages();

// Get vacancies
$vagas = $vagaService->list($where, 'data DESC', $obPagination->getLimit());

// Check user permissions for template
$podeEditar = RoleManager::hasPermission($usuarioId, 'vaga.editar');
$podeDeletar = RoleManager::hasPermission($usuarioId, 'vaga.deletar');
$podeCriar = RoleManager::hasPermission($usuarioId, 'vaga.criar');

// Pagination query (preserve filters)
$queryParams = $_GET;
unset($queryParams['status'], $queryParams['pagina'], $queryParams['r']);
$queryString = http_build_query($queryParams);

// Load templates
View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancies-list.php', [
  'alerta' => $alerta,
  'vagas' => $vagas,
  'podeEditar' => $podeEditar,
  'podeDeletar' => $podeDeletar,
  'podeCriar' => $podeCriar,
  'paginacao' => $paginacao,
  'busca' => $busca,
  'filtroStatus' => $filtroStatus,
  'queryString' => $queryString
]);
View::render(VIEW_PATH . '/layout/footer.php');

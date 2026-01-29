<?php

require __DIR__ . '/vendor/autoload.php';


use \App\Entity\Vaga;
use \App\Db\Pagination;
use \App\Session\Login;
use \App\Util\RoleManager;

// Require user to be logged in
Login::requireLogin();

// Get logged-in user info
$usuarioLogado = Login::getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

// Check if user can view vagas
RoleManager::requirePermission($usuarioId, 'vaga.visualizar');

// Get search input
$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filtroStatus = filter_input(INPUT_GET, 'filtroStatus', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filtroStatus = in_array($filtroStatus, ['s', 'n']) ? $filtroStatus : '';

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
$quantidadeVagas = Vaga::getQuantidadeVagas($where);

// Pagination
$paginaAtual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$obPagination = new Pagination($quantidadeVagas, $paginaAtual, 5);

// Get vacancies
$vagas = Vaga::getVagas($where, 'data DESC', $obPagination->getLimit());

// Check user permissions for template
$podeEditar = RoleManager::hasPermission($usuarioId, 'vaga.editar');
$podeDeletar = RoleManager::hasPermission($usuarioId, 'vaga.deletar');
$podeCriar = RoleManager::hasPermission($usuarioId, 'vaga.criar');

// Load templates
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/listagem.php';
include __DIR__ . '/includes/footer.php';

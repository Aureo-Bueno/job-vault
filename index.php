<?php

require __DIR__ . '/vendor/autoload.php';

use \App\Entity\Vaga;
use \App\Db\Pagination;
use \App\Session\Login;

// Require user to be logged in
Login::requireLogin();

// Get logged-in user info
$usuarioLogado = Login::getUsuarioLogado();

// Get search input (null-safe)
$busca = filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// Get status filter (null-safe)
$filtroStatus = filter_input(INPUT_GET, 'filtroStatus', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$filtroStatus = in_array($filtroStatus, ['s', 'n']) ? $filtroStatus : '';

// Build WHERE conditions dynamically
$condicoes = [];

// Add search condition if provided
if (!empty($busca)) {
  $searchTerm = str_replace(' ', '%', addslashes($busca));
  $condicoes[] = "titulo LIKE '%{$searchTerm}%'";
}

// Add status filter if provided
if (!empty($filtroStatus)) {
  $condicoes[] = "ativo = '{$filtroStatus}'";
}

// Combine conditions
$where = !empty($condicoes) ? implode(' AND ', $condicoes) : null;

// Get total number of vacancies
$quantidadeVagas = Vaga::getQuantidadeVagas($where);

// Initialize pagination
$paginaAtual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?? 1;
$obPagination = new Pagination($quantidadeVagas, $paginaAtual, 5);

// Get paginated vacancies
$vagas = Vaga::getVagas($where, 'data DESC', $obPagination->getLimit());

// Load templates
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/listagem.php';
include __DIR__ . '/includes/footer.php';

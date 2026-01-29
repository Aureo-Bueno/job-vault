<?php
require BASE_PATH . '/vendor/autoload.php';

use \App\Util\RoleManager;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;

// Require login
$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioId = $authService->getUsuarioLogado()['id'];

// Check permission to create/edit
RoleManager::requirePermission($usuarioId, 'vaga.editar');

$tituloPagina = 'Editar vaga';

//VALIDA O ID
if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
  header('location: index.php?r=home&status=error');
  exit;
}

//CONSULTA A VAGA
$vagaService = AppContainer::vagaService();
$obVaga = $vagaService->getById((int) $_GET['id']);

// VALIDAR A VAGA
if (!$obVaga) {
  header('location: index.php?r=home&status=error');
  exit;
}




//VALIDAÇAO DO POST
if (isset($_POST['titulo'], $_POST['descricao'], $_POST['ativo'])) {

  $obVaga->titulo = $_POST['titulo'];
  $obVaga->descricao = $_POST['descricao'];
  $obVaga->ativo = $_POST['ativo'];
  $vagaService->update($obVaga);


  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'obVaga' => $obVaga,
  'tituloPagina' => $tituloPagina
]);
View::render(VIEW_PATH . '/layout/footer.php');

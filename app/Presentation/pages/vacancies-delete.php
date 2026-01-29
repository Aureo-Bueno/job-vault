<?php
require BASE_PATH . '/vendor/autoload.php';

use \App\Util\RoleManager;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;

// Require login
$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioId = $authService->getUsuarioLogado()['id'];

// Check permission to delete
RoleManager::requirePermission($usuarioId, 'vaga.deletar');


if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
  header('location: index.php?r=home&status=error');
  exit;
}

$vagaService = AppContainer::vagaService();
$obVaga = $vagaService->getById((int) $_GET['id']);

if (!$obVaga) {
  header('location: index.php?r=home&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  $vagaService->delete((int) $obVaga->id);


  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-delete-confirm.php', [
  'obVaga' => $obVaga
]);
View::render(VIEW_PATH . '/layout/footer.php');

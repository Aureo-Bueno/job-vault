<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioLogado = $authService->getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

RoleManager::requirePermission($usuarioId, 'usuario.deletar');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('location: index.php?r=usuarios&status=error');
  exit;
}

$usuarioService = AppContainer::usuarioService();
$usuario = $usuarioService->getById((int) $_GET['id']);
if (!$usuario) {
  header('location: index.php?r=usuarios&status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  $usuarioService->delete((int) $usuario->id);
  header('location: index.php?r=usuarios&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-delete-confirm.php', [
  'usuario' => $usuario
]);
View::render(VIEW_PATH . '/layout/footer.php');

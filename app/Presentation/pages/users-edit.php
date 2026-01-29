<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioLogado = $authService->getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

RoleManager::requirePermission($usuarioId, 'usuario.editar');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('location: index.php?r=usuarios&status=error');
  exit;
}

$usuarioService = AppContainer::usuarioService();
$roleService = AppContainer::roleService();

$usuario = $usuarioService->getById((int) $_GET['id']);
if (!$usuario) {
  header('location: index.php?r=usuarios&status=error');
  exit;
}

$tituloPagina = 'Editar Usuário';
$alerta = null;

$podeAtribuirRole = RoleManager::hasPermission($usuarioId, 'usuario.atribuir_role');
$roles = $podeAtribuirRole ? $roleService->listRoles() : [];

if (isset($_POST['nome'], $_POST['email'])) {
  $usuario->nome = $_POST['nome'];
  $usuario->email = $_POST['email'];

  if ($podeAtribuirRole && isset($_POST['role_id']) && is_numeric($_POST['role_id'])) {
    $usuario->roleId = (int) $_POST['role_id'];
  }

  $senhaNova = $_POST['senha'] ?? null;
  $ok = $usuarioService->update($usuario, $senhaNova);
  if ($ok) {
    header('location: index.php?r=usuarios&status=success');
    exit;
  }

  $alerta = [
    'tipo' => 'danger',
    'icone' => 'bi bi-exclamation-circle-fill',
    'mensagem' => 'Não foi possível atualizar o usuário.'
  ];
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-form.php', [
  'usuario' => $usuario,
  'roles' => $roles,
  'podeAtribuirRole' => $podeAtribuirRole,
  'modo' => 'editar',
  'tituloPagina' => $tituloPagina,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

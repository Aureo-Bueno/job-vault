<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Domain\Model\Usuario;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$authService->requireLogin();
$usuarioLogado = $authService->getUsuarioLogado();
$usuarioId = $usuarioLogado['id'];

RoleManager::requirePermission($usuarioId, 'usuario.editar');

$usuarioService = AppContainer::usuarioService();
$roleService = AppContainer::roleService();

$tituloPagina = 'Cadastrar Usuário';
$alerta = null;

$podeAtribuirRole = RoleManager::hasPermission($usuarioId, 'usuario.atribuir_role');
$roles = $podeAtribuirRole ? $roleService->listRoles() : [];

$usuario = new Usuario();

if (isset($_POST['nome'], $_POST['email'], $_POST['senha'])) {
  $email = $_POST['email'];
  $existing = $usuarioService->getByEmail($email);
  if ($existing) {
    $alerta = [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'O Email digitado já está em uso.'
    ];
  } else {
    $usuario->nome = $_POST['nome'];
    $usuario->email = $email;
    if ($podeAtribuirRole && isset($_POST['role_id']) && is_numeric($_POST['role_id'])) {
      $usuario->roleId = (int) $_POST['role_id'];
    }

    $criado = $usuarioService->create($usuario, $_POST['senha']);
    if ($criado) {
      header('location: index.php?r=usuarios&status=success');
      exit;
    }

    $alerta = [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'Não foi possível criar o usuário.'
    ];
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/user-form.php', [
  'usuario' => $usuario,
  'roles' => $roles,
  'podeAtribuirRole' => $podeAtribuirRole,
  'modo' => 'criar',
  'tituloPagina' => $tituloPagina,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

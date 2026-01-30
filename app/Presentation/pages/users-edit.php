<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;
use App\Util\IdValidator;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$loggedUserId = $loggedUser['id'];

RoleManager::requirePermission($loggedUserId, 'user.edit');

$targetUserId = $_GET['id'] ?? null;
if (!IdValidator::isValid($targetUserId)) {
  header('location: index.php?r=users&status=error');
  exit;
}

$userService = AppContainer::userService();
$roleService = AppContainer::roleService();

$user = $userService->getById((string) $targetUserId);
if (!$user) {
  header('location: index.php?r=users&status=error');
  exit;
}

$pageTitle = 'Editar Usuário';
$alerta = null;

$canAssignRole = RoleManager::hasPermission($loggedUserId, 'user.assign_role');
$roles = $canAssignRole ? $roleService->listRoles() : [];

if (isset($_POST['name'], $_POST['email'])) {
  $user->name = $_POST['name'];
  $user->email = $_POST['email'];

  if ($canAssignRole && isset($_POST['role_id']) && IdValidator::isValid($_POST['role_id'])) {
    $user->roleId = (string) $_POST['role_id'];
  }

  $password = $_POST['password'] ?? null;
  $ok = $userService->update($user, $password);
  if ($ok) {
    header('location: index.php?r=users&status=success');
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
  'user' => $user,
  'roles' => $roles,
  'canAssignRole' => $canAssignRole,
  'modo' => 'editar',
  'tituloPagina' => $pageTitle,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

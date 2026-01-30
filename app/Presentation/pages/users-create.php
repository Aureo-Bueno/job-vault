<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Domain\Model\User;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\RoleManager;
use App\Util\IdValidator;

$authService = AppContainer::authService();
$authService->requireLogin();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'];

RoleManager::requirePermission($userId, 'user.edit');

$userService = AppContainer::userService();
$roleService = AppContainer::roleService();

$pageTitle = 'Cadastrar Usuário';
$alerta = null;

$canAssignRole = RoleManager::hasPermission($userId, 'user.assign_role');
$roles = $canAssignRole ? $roleService->listRoles() : [];

$user = new User();

if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
  $email = $_POST['email'];
  $existing = $userService->getByEmail($email);
  if ($existing) {
    $alerta = [
      'tipo' => 'danger',
      'icone' => 'bi bi-exclamation-circle-fill',
      'mensagem' => 'O Email digitado já está em uso.'
    ];
  } else {
    $user->name = $_POST['name'];
    $user->email = $email;
    if ($canAssignRole && isset($_POST['role_id']) && IdValidator::isValid($_POST['role_id'])) {
      $user->roleId = (string) $_POST['role_id'];
    }

    $criado = $userService->create($user, $_POST['password']);
    if ($criado) {
      header('location: index.php?r=users&status=success');
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
  'user' => $user,
  'roles' => $roles,
  'canAssignRole' => $canAssignRole,
  'modo' => 'criar',
  'tituloPagina' => $pageTitle,
  'alerta' => $alerta
]);
View::render(VIEW_PATH . '/layout/footer.php');

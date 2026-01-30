<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;

$alertaLogin = "";
$alertaCadastro = "";

$authService = AppContainer::authService();
$authService->requireLogout();

if (isset($_POST['acao'])) {
  switch ($_POST['acao']) {
    case 'logar':
      $user = $authService->authenticate($_POST['email'], $_POST['password']);
      if (!$user) {
        $alertaLogin = "Email ou Senha Inválidos";
        break;
      }
      $authService->login($user);
      break;

    case 'cadastrar':
      if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
        $registerResult = $authService->register($_POST['name'], $_POST['email'], $_POST['password']);
        $user = $registerResult['user'] ?? null;
        if (!$user) {
          $alertaCadastro = $registerResult['error'] ?? 'Não foi possível criar o usuário';
          break;
        }

        $authService->login($user);
      }
      break;
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/auth-form.php', [
  'alertaLogin' => $alertaLogin,
  'alertaCadastro' => $alertaCadastro
]);
View::render(VIEW_PATH . '/layout/footer.php');

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
      $obUsuario = $authService->authenticate($_POST['email'], $_POST['senha']);
      if (!$obUsuario) {
        $alertaLogin = "Email ou Senha Inválidos";
        break;
      }
      $authService->login($obUsuario);
      break;

    case 'cadastrar':
      if (isset($_POST['nome'], $_POST['email'], $_POST['senha'])) {
        $resultadoCadastro = $authService->register($_POST['nome'], $_POST['email'], $_POST['senha']);
        $obUsuario = $resultadoCadastro['user'] ?? null;
        if (!$obUsuario) {
          $alertaCadastro = $resultadoCadastro['error'] ?? 'Não foi possível criar o usuário';
          break;
        }

        $authService->login($obUsuario);
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

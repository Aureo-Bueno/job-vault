<?php
require __DIR__ . '/vendor/autoload.php';

use App\Entity\Usuario;
use App\Session\Login;

$alertaLogin = "";
$alertaCadastro = "";

Login::requireLogout();

if (isset($_POST['acao'])) {
  switch ($_POST['acao']) {
    case 'logar':
      $obUsuario = Usuario::getUsuariosEmail($_POST['email']);
      if (!$obUsuario instanceof Usuario || !password_verify($_POST['senha'], $obUsuario->senha)) {
        $alertaLogin = "Email ou Senha Inválidos";
        break;
      }
      Login::login($obUsuario);
      break;

    case 'cadastrar':
      if (isset($_POST['nome'], $_POST['email'], $_POST['senha'])) {
        $obUsuario = Usuario::getUsuariosEmail($_POST['email']);
        if ($obUsuario instanceof Usuario) {
          $alertaCadastro = "O Email digitado já está em uso";
          break;
        }

        $obUsuario = new Usuario();
        $obUsuario->nome = $_POST['nome'];
        $obUsuario->email = $_POST['email'];
        $obUsuario->senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $obUsuario->cadastrar();
        Login::login($obUsuario);
      }
      break;
  }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/formulario-login.php';
include __DIR__ . '/includes/footer.php';

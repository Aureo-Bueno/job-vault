<?php

/**
 * Authentication page controller.
 *
 * Responsibilities:
 * - renders login/registration form for guests;
 * - validates CSRF token for submitted actions;
 * - dispatches login and registration handlers.
 */

require BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;
use App\Util\Csrf;

$alertaLogin = '';
$alertaCadastro = '';

$authService = AppContainer::authService();
$authService->requireLogout();

$action = (string) ($_POST['acao'] ?? '');
if ($action !== '') {
  $isValidCsrfRequest = Csrf::validateFromRequest();

  if (!$isValidCsrfRequest) {
    $invalidCsrfMessage = 'Não foi possível validar a requisição. Tente novamente.';
    if ($action === 'cadastrar') {
      $alertaCadastro = $invalidCsrfMessage;
    }
    if ($action !== 'cadastrar') {
      $alertaLogin = $invalidCsrfMessage;
    }
  }

  if ($isValidCsrfRequest && $action === 'logar') {
    $alertaLogin = handleLogin($authService);
  }

  if ($isValidCsrfRequest && $action === 'cadastrar') {
    $alertaCadastro = handleRegister($authService);
  }
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/auth-form.php', [
  'alertaLogin' => $alertaLogin,
  'alertaCadastro' => $alertaCadastro
]);
View::render(VIEW_PATH . '/layout/footer.php');

/**
 * Executes login flow using submitted credentials.
 *
 * @return string Error message when authentication fails, empty string otherwise.
 */
function handleLogin(\App\Application\Service\AuthService $authService): string
{
  $email = (string) ($_POST['email'] ?? '');
  $password = (string) ($_POST['password'] ?? '');

  $authenticatedUser = $authService->authenticate($email, $password);
  if (!$authenticatedUser) {
    return 'Email ou Senha Inválidos';
  }

  $authService->login($authenticatedUser);
  return '';
}

/**
 * Executes registration flow for submitted user data.
 *
 * @return string Error message when registration fails, empty string otherwise.
 */
function handleRegister(\App\Application\Service\AuthService $authService): string
{
  if (!isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    return 'Preencha os campos obrigatórios.';
  }

  $registerResult = $authService->register(
    (string) $_POST['name'],
    (string) $_POST['email'],
    (string) $_POST['password']
  );

  $registeredUser = $registerResult['user'] ?? null;
  if (!$registeredUser) {
    return $registerResult['error'] ?? 'Não foi possível criar o usuário';
  }

  $authService->login($registeredUser);
  return '';
}

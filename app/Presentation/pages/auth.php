<?php

/**
 * Login / sign-up page (guests only).
 */

use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

PageAccess::requireGuest();

$authService = AppContainer::authService();
$alertaLogin = '';
$alertaCadastro = '';
$action = (string) ($_POST['acao'] ?? '');
$accountType = ($_POST['account_type'] ?? 'candidate') === 'company' ? 'company' : 'candidate';

if ($action !== '' && !Csrf::validateFromRequest()) {
  $invalidCsrf = 'Não foi possível validar a requisição. Tente novamente.';
  if ($action === 'cadastrar') {
    $alertaCadastro = $invalidCsrf;
  } else {
    $alertaLogin = $invalidCsrf;
  }
  $action = '';
}

if ($action === 'logar') {
  $user = $authService->authenticate((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
  if ($user !== null) {
    $authService->login($user);
    HttpRedirect::route('home');
  }

  $alertaLogin = 'Email ou Senha Inválidos';
}

if ($action === 'cadastrar') {
  $registered = null;

  try {
    if ($accountType === 'company') {
      $registered = $authService->registerCompany(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['company_name'] ?? ''),
        (string) ($_POST['company_sector'] ?? 'Tecnologia')
      );
    } else {
      $registered = $authService->register(
        (string) ($_POST['name'] ?? ''),
        (string) ($_POST['email'] ?? ''),
        (string) ($_POST['password'] ?? '')
      );
    }
  } catch (\Throwable $exception) {
    $alertaCadastro = ExceptionHttpMapper::toPayload($exception)['message'];
  }

  if ($registered !== null) {
    $authService->login($registered);
    if ($accountType === 'company') {
      HttpRedirect::withSuccess('home', 'Empresa cadastrada com sucesso! Publique sua primeira vaga.');
    }

    HttpRedirect::withSuccess('profile', 'Conta criada! Descreva seu perfil e adicione seus tópicos para ver seu match nas vagas.');
  }
}

Layout::page('/pages/auth-form.php', [
  'alertaLogin' => $alertaLogin,
  'alertaCadastro' => $alertaCadastro,
  'activeTab' => $alertaCadastro !== '' ? 'signup' : 'login',
  'loginEmail' => $action === 'logar' ? trim((string) ($_POST['email'] ?? '')) : '',
  'signupName' => $action === 'cadastrar' ? trim((string) ($_POST['name'] ?? '')) : '',
  'signupEmail' => $action === 'cadastrar' ? trim((string) ($_POST['email'] ?? '')) : '',
  'accountType' => $accountType,
  'companyName' => $action === 'cadastrar' ? trim((string) ($_POST['company_name'] ?? '')) : '',
  'companySector' => $action === 'cadastrar' ? trim((string) ($_POST['company_sector'] ?? 'Tecnologia')) : 'Tecnologia',
]);

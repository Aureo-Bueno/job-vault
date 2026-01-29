<?php

namespace App\Application\Service;

use App\Domain\Model\Usuario;
use App\Domain\Repository\UsuarioRepositoryInterface;
use App\Util\Logger;

class AuthService
{
  private UsuarioRepositoryInterface $usuarioRepository;
  private Logger $logger;

  public function __construct(UsuarioRepositoryInterface $usuarioRepository)
  {
    $this->usuarioRepository = $usuarioRepository;
    $this->logger = new Logger('login');
  }

  public function authenticate(string $email, string $senha): ?Usuario
  {
    $usuario = $this->usuarioRepository->findByEmail($email);

    if (!$usuario) {
      return null;
    }

    return password_verify($senha, $usuario->senha) ? $usuario : null;
  }

  /** @return array{user:?Usuario,error:?string} */
  public function register(string $nome, string $email, string $senha): array
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['user' => null, 'error' => 'Email inválido'];
    }

    if (strlen($senha) < 6) {
      return ['user' => null, 'error' => 'A senha deve ter no mínimo 6 caracteres'];
    }

    $existing = $this->usuarioRepository->findByEmail($email);
    if ($existing) {
      return ['user' => null, 'error' => 'O Email digitado já está em uso'];
    }

    $usuario = new Usuario(
      null,
      $nome,
      $email,
      password_hash($senha, PASSWORD_DEFAULT),
      null
    );

    $id = $this->usuarioRepository->create($usuario);
    if (!$id) {
      return ['user' => null, 'error' => 'Não foi possível criar o usuário'];
    }

    $usuario->id = $id;
    return ['user' => $usuario, 'error' => null];
  }

  public function login(Usuario $usuario): void
  {
    $this->initSession();

    $roleId = $usuario->roleId ?? ($usuario->role_id ?? null);

    $_SESSION['usuario'] = [
      'id' => $usuario->id,
      'nome' => $usuario->nome,
      'email' => $usuario->email,
      'role_id' => $roleId
    ];

    $logger = $this->logger;
    $userId = $usuario->id;
    $email = $usuario->email;
    register_shutdown_function(function () use ($logger, $userId, $email, $roleId) {
      $logger->info('User logged in', [
        'user_id' => $userId,
        'email' => $email,
        'role_id' => $roleId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
    });

    header('Location: index.php?r=home');
    exit;
  }

  public function logout(): void
  {
    $this->initSession();

    $userEmail = $_SESSION['usuario']['email'] ?? 'unknown';
    unset($_SESSION['usuario']);

    $logger = $this->logger;
    register_shutdown_function(function () use ($logger, $userEmail) {
      $logger->info('User logged out', [
        'email' => $userEmail,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
    });

    header('Location: index.php?r=login');
    exit;
  }

  public function getUsuarioLogado(): ?array
  {
    $this->initSession();
    return $this->isLogged() ? $_SESSION['usuario'] : null;
  }

  public function isLogged(): bool
  {
    $this->initSession();
    return isset($_SESSION['usuario']['id']);
  }

  public function requireLogin(): void
  {
    if (!$this->isLogged()) {
      $this->logger->warning('Unauthorized access attempt', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
      ]);

      header('Location: index.php?r=login');
      exit;
    }
  }

  public function requireLogout(): void
  {
    if ($this->isLogged()) {
      header('Location: index.php?r=home');
      exit;
    }
  }

  private function initSession(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }
}

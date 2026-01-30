<?php

namespace App\Application\Service;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Util\Logger;

class AuthService
{
  private UserRepositoryInterface $userRepository;
  private Logger $logger;

  public function __construct(UserRepositoryInterface $userRepository)
  {
    $this->userRepository = $userRepository;
    $this->logger = new Logger('login');
  }

  public function authenticate(string $email, string $password): ?User
  {
    $user = $this->userRepository->findByEmail($email);

    if (!$user) {
      return null;
    }

    return password_verify($password, $user->password) ? $user : null;
  }

  /** @return array{user:?User,error:?string} */
  public function register(string $name, string $email, string $password): array
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['user' => null, 'error' => 'Email inválido'];
    }

    if (strlen($password) < 6) {
      return ['user' => null, 'error' => 'A senha deve ter no mínimo 6 caracteres'];
    }

    $existing = $this->userRepository->findByEmail($email);
    if ($existing) {
      return ['user' => null, 'error' => 'O Email digitado já está em uso'];
    }

    $user = new User(
      null,
      $name,
      $email,
      password_hash($password, PASSWORD_DEFAULT),
      null
    );

    $id = $this->userRepository->create($user);
    if (!$id) {
      return ['user' => null, 'error' => 'Não foi possível criar o usuário'];
    }

    $user->id = $id;
    return ['user' => $user, 'error' => null];
  }

  public function login(User $user): void
  {
    $this->initSession();

    $roleId = $user->roleId ?? ($user->role_id ?? null);

    $_SESSION['user'] = [
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'role_id' => $roleId
    ];

    $logger = $this->logger;
    $userId = $user->id;
    $email = $user->email;
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

    $userEmail = $_SESSION['user']['email'] ?? 'unknown';
    unset($_SESSION['user']);

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

  public function getLoggedUser(): ?array
  {
    $this->initSession();
    return $this->isLogged() ? $_SESSION['user'] : null;
  }

  public function isLogged(): bool
  {
    $this->initSession();
    return isset($_SESSION['user']['id']);
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

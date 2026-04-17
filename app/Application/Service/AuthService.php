<?php

namespace App\Application\Service;

use App\Domain\Entity\UserAccount;
use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Util\Logger;

/**
 * Handles authentication, registration and session lifecycle operations.
 */
class AuthService
{
  private UserRepositoryInterface $userRepository;
  private Logger $logger;

  public function __construct(UserRepositoryInterface $userRepository)
  {
    $this->userRepository = $userRepository;
    $this->logger = new Logger('login');
  }

  /**
   * Authenticates credentials and returns the corresponding user model.
   */
  public function authenticate(string $email, string $password): ?User
  {
    $loginAttempt = UserAccount::register('auth-user', $email, $password);
    $loginUserEntity = $loginAttempt['entity'] ?? null;
    if (!$loginUserEntity) {
      return null;
    }

    $user = $this->userRepository->findByEmail($loginUserEntity->email());
    if (!$user) {
      return null;
    }

    $existingUserEntity = UserAccount::restore($user);
    if (!$existingUserEntity) {
      return null;
    }

    if (!$existingUserEntity->authenticate($password)) {
      return null;
    }

    return $existingUserEntity->toModel();
  }

  /**
   * Registers a new user and returns either the created user or an error message.
   *
   * @return array{user:?User,error:?string}
   */
  public function register(string $name, string $email, string $password): array
  {
    $registration = UserAccount::register($name, $email, $password);
    $newUserEntity = $registration['entity'] ?? null;
    if (!$newUserEntity) {
      return ['user' => null, 'error' => $registration['error'] ?? 'Dados inválidos'];
    }

    $existing = $this->userRepository->findByEmail($newUserEntity->email());
    if ($existing) {
      return ['user' => null, 'error' => 'O Email digitado já está em uso'];
    }

    $user = $newUserEntity->toModel();
    $id = $this->userRepository->create($user);
    if (!$id) {
      return ['user' => null, 'error' => 'Não foi possível criar o usuário'];
    }

    $newUserEntity->setId($id);
    return ['user' => $newUserEntity->toModel(), 'error' => null];
  }

  /**
   * Starts authenticated session data and redirects user to home.
   */
  public function login(User $user): void
  {
    $this->initSession();
    session_regenerate_id(true);

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

  /**
   * Destroys active session and redirects to login route.
   */
  public function logout(): void
  {
    $this->initSession();

    $userEmail = $_SESSION['user']['email'] ?? 'unknown';
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool) ($params['secure'] ?? false),
        (bool) ($params['httponly'] ?? true)
      );
    }

    session_destroy();

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

  /**
   * Returns logged user payload stored in session.
   */
  public function getLoggedUser(): ?array
  {
    $this->initSession();
    return $this->isLogged() ? $_SESSION['user'] : null;
  }

  /**
   * Checks whether session has an authenticated user.
   */
  public function isLogged(): bool
  {
    $this->initSession();
    return isset($_SESSION['user']['id']);
  }

  /**
   * Enforces authentication and redirects to login when missing.
   */
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

  /**
   * Enforces guest-only routes and redirects logged users to home.
   */
  public function requireLogout(): void
  {
    if ($this->isLogged()) {
      header('Location: index.php?r=home');
      exit;
    }
  }

  /**
   * Initializes PHP session with secure cookie defaults.
   */
  private function initSession(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $this->isSecureRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
      ]);
      session_start();
    }
  }

  /**
   * Detects whether current HTTP request is using HTTPS.
   */
  private function isSecureRequest(): bool
  {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      return true;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strtolower((string) $forwardedProto) === 'https';
  }
}

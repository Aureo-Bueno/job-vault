<?php

namespace App\Application\Auth;

use App\Application\Ports\LoggerInterface;
use App\Application\Ports\SessionInterface;
use App\Application\Security\AuthenticatedUser;
use App\Application\Security\CurrentUserProvider;
use App\Application\Users\UserRegistrar;
use App\Domain\User\EmailAddress;
use App\Domain\User\UserRepositoryInterface;

/**
 * Authentication use cases. Knows nothing about HTTP: redirects are a presentation concern.
 */
final class AuthService implements CurrentUserProvider
{
  private const SESSION_KEY = 'user';

  public function __construct(
    private UserRepositoryInterface $users,
    private UserRegistrar $registrar,
    private SessionInterface $session,
    private LoggerInterface $logger
  ) {
  }

  /**
   * Returns the user when the credentials are valid, null otherwise.
   */
  public function authenticate(string $email, string $password): ?AuthenticatedUser
  {
    $emailAddress = EmailAddress::tryFrom($email);
    $user = $emailAddress ? $this->users->findByEmail($emailAddress) : null;

    if ($user === null || !$user->verifyPassword($password)) {
      $this->logger->warning('Login failed', ['email' => trim($email)]);
      return null;
    }

    return AuthenticatedUser::fromUser($user);
  }

  /**
   * Self sign-up: always gets the default role.
   *
   * @throws \App\Domain\Exception\DomainException on invalid data or e-mail in use
   */
  public function register(string $name, string $email, string $password): AuthenticatedUser
  {
    $user = $this->registrar->register($name, $email, $password);
    $this->logger->info('User registered', ['user_id' => $user->id()->value()]);

    return AuthenticatedUser::fromUser($user);
  }

  public function login(AuthenticatedUser $user): void
  {
    $this->session->regenerate();
    $this->session->set(self::SESSION_KEY, $user->toArray());
    $this->logger->info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);
  }

  public function logout(): void
  {
    $user = $this->currentUser();
    $this->session->destroy();
    $this->logger->info('User logged out', ['email' => $user?->email ?? 'unknown']);
  }

  public function currentUser(): ?AuthenticatedUser
  {
    $data = $this->session->get(self::SESSION_KEY);
    return is_array($data) ? AuthenticatedUser::fromArray($data) : null;
  }

  public function isAuthenticated(): bool
  {
    return $this->currentUser() !== null;
  }
}

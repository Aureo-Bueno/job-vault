<?php

namespace Tests\Support;

use App\Application\Service\AuthService;
use RuntimeException;

class FakeAuthService extends AuthService
{
  private bool $logged;
  private ?array $user;

  public function __construct(bool $logged = true, ?array $user = null)
  {
    $this->logged = $logged;
    $this->user = $user ?? ['id' => '1', 'name' => 'Test User', 'email' => 'test@example.com'];
  }

  public function requireLogin(): void
  {
    if (!$this->logged) {
      throw new RuntimeException('Not authenticated.');
    }
  }

  public function getLoggedUser(): ?array
  {
    return $this->logged ? $this->user : null;
  }

  public function isLogged(): bool
  {
    return $this->logged;
  }
}

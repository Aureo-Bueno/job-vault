<?php

namespace Tests\Support;

use App\Application\Security\AuthenticatedUser;
use App\Application\Security\CurrentUserProvider;

final class FakeCurrentUser implements CurrentUserProvider
{
  public function __construct(private ?AuthenticatedUser $user = null)
  {
  }

  public static function as(string $id): self
  {
    return new self(new AuthenticatedUser($id, 'Test User', 'test@example.com'));
  }

  public function currentUser(): ?AuthenticatedUser
  {
    return $this->user;
  }
}

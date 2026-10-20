<?php

namespace App\Application\Security;

use App\Domain\User\User;

/**
 * Snapshot of the logged user kept in the session.
 */
final class AuthenticatedUser
{
  public function __construct(
    public readonly string $id,
    public readonly string $name,
    public readonly string $email
  ) {
  }

  public static function fromUser(User $user): self
  {
    return new self($user->id()->value(), $user->name()->value(), $user->email()->value());
  }

  /**
   * @param array<string,mixed> $data
   */
  public static function fromArray(array $data): ?self
  {
    $id = $data['id'] ?? null;
    if (!is_scalar($id) || (string) $id === '') {
      return null;
    }

    return new self((string) $id, (string) ($data['name'] ?? ''), (string) ($data['email'] ?? ''));
  }

  /** @return array{id:string,name:string,email:string} */
  public function toArray(): array
  {
    return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
  }

  public function initial(): string
  {
    return mb_strtoupper(mb_substr($this->name, 0, 1));
  }
}

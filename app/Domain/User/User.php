<?php

namespace App\Domain\User;

use App\Domain\Role\RoleId;

/**
 * User aggregate: owns identity, credentials and role assignment.
 */
final class User
{
  private function __construct(
    private UserId $id,
    private PersonName $name,
    private EmailAddress $email,
    private PasswordHash $password,
    private ?RoleId $roleId
  ) {
  }

  public static function register(
    UserId $id,
    PersonName $name,
    EmailAddress $email,
    PlainPassword $password,
    ?RoleId $roleId = null
  ): self {
    return new self($id, $name, $email, PasswordHash::fromPlain($password), $roleId);
  }

  /**
   * Rebuilds a user from persisted state (no new business event).
   */
  public static function reconstitute(
    UserId $id,
    PersonName $name,
    EmailAddress $email,
    PasswordHash $password,
    ?RoleId $roleId
  ): self {
    return new self($id, $name, $email, $password, $roleId);
  }

  public function verifyPassword(string $plainPassword): bool
  {
    return $this->password->matches($plainPassword);
  }

  public function changeProfile(PersonName $name, EmailAddress $email): void
  {
    $this->name = $name;
    $this->email = $email;
  }

  public function changePassword(PlainPassword $password): void
  {
    $this->password = PasswordHash::fromPlain($password);
  }

  public function assignRole(?RoleId $roleId): void
  {
    $this->roleId = $roleId;
  }

  public function hasRole(): bool
  {
    return $this->roleId !== null;
  }

  public function isSameAs(UserId $id): bool
  {
    return $this->id->equals($id);
  }

  public function id(): UserId
  {
    return $this->id;
  }

  public function name(): PersonName
  {
    return $this->name;
  }

  public function email(): EmailAddress
  {
    return $this->email;
  }

  public function passwordHash(): PasswordHash
  {
    return $this->password;
  }

  public function roleId(): ?RoleId
  {
    return $this->roleId;
  }
}

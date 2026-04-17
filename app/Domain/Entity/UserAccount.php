<?php

namespace App\Domain\Entity;

use App\Domain\Model\User;
use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\PlainPassword;

final class UserAccount
{
  private ?string $id;
  private string $name;
  private EmailAddress $emailAddress;
  private string $passwordHash;
  private ?string $roleId;

  private function __construct(
    ?string $id,
    string $name,
    EmailAddress $emailAddress,
    string $passwordHash,
    ?string $roleId
  ) {
    $this->id = $id;
    $this->name = $name;
    $this->emailAddress = $emailAddress;
    $this->passwordHash = $passwordHash;
    $this->roleId = $roleId;
  }

  /** @return array{entity:?self,error:?string} */
  public static function register(string $name, string $email, string $plainPassword, ?string $roleId = null): array
  {
    $normalizedName = trim($name);
    if ($normalizedName === '') {
      return ['entity' => null, 'error' => 'Nome inválido'];
    }

    $emailAddress = EmailAddress::fromString($email);
    if (!$emailAddress) {
      return ['entity' => null, 'error' => 'Email inválido'];
    }

    $password = PlainPassword::fromString($plainPassword);
    if (!$password) {
      return [
        'entity' => null,
        'error' => 'A senha deve ter no mínimo ' . PlainPassword::minimumLength() . ' caracteres'
      ];
    }

    return [
      'entity' => new self(
        null,
        $normalizedName,
        $emailAddress,
        $password->hash(),
        $roleId
      ),
      'error' => null
    ];
  }

  public static function restore(User $user): ?self
  {
    $normalizedName = trim($user->name);
    if ($normalizedName === '') {
      return null;
    }

    $emailAddress = EmailAddress::fromString($user->email);
    if (!$emailAddress) {
      return null;
    }

    return new self(
      $user->id,
      $normalizedName,
      $emailAddress,
      $user->password,
      $user->roleId
    );
  }

  public function authenticate(string $plainPassword): bool
  {
    $password = PlainPassword::fromString($plainPassword);
    if (!$password) {
      return false;
    }

    return $password->matchesHash($this->passwordHash);
  }

  public function applyProfile(string $name, string $email, ?string $roleId = null): bool
  {
    $normalizedName = trim($name);
    if ($normalizedName === '') {
      return false;
    }

    $emailAddress = EmailAddress::fromString($email);
    if (!$emailAddress) {
      return false;
    }

    $this->name = $normalizedName;
    $this->emailAddress = $emailAddress;
    $this->roleId = $roleId;
    return true;
  }

  public function applyPassword(string $plainPassword): bool
  {
    $password = PlainPassword::fromString($plainPassword);
    if (!$password) {
      return false;
    }

    $this->passwordHash = $password->hash();
    return true;
  }

  public function setId(string $id): void
  {
    $this->id = $id;
  }

  public function email(): string
  {
    return (string) $this->emailAddress;
  }

  public function toModel(): User
  {
    return new User(
      $this->id,
      $this->name,
      (string) $this->emailAddress,
      $this->passwordHash,
      $this->roleId
    );
  }
}

<?php

namespace App\Application\Users;

use App\Application\Exceptions\NotFoundException;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;
use App\Domain\User\EmailAddress;
use App\Domain\User\EmailAlreadyInUseException;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;

/**
 * Registration policy shared by self sign-up and the admin panel:
 * unique e-mail and default role when none is chosen.
 */
final class UserRegistrar
{
  public function __construct(
    private UserRepositoryInterface $users,
    private RoleRepositoryInterface $roles
  ) {
  }

  public function register(string $name, string $email, string $password, ?string $roleId = null): User
  {
    $personName = PersonName::fromString($name);
    $emailAddress = EmailAddress::fromString($email);
    $plainPassword = PlainPassword::fromString($password);

    $this->ensureEmailIsAvailable($emailAddress);

    $user = User::register(
      UserId::generate(),
      $personName,
      $emailAddress,
      $plainPassword,
      $this->resolveRole($roleId)
    );
    $this->users->save($user);

    return $user;
  }

  /**
   * @param UserId|null $owner user allowed to already own the e-mail (for updates)
   */
  public function ensureEmailIsAvailable(EmailAddress $email, ?UserId $owner = null): void
  {
    $existing = $this->users->findByEmail($email);
    if ($existing !== null && ($owner === null || !$existing->isSameAs($owner))) {
      throw new EmailAlreadyInUseException();
    }
  }

  /**
   * Validates an explicit role, or falls back to the default role.
   */
  public function resolveRole(?string $roleId): ?RoleId
  {
    if ($roleId === null || trim($roleId) === '') {
      return $this->roles->findByName(RoleName::defaultUser())?->id();
    }

    return $this->existingRole($roleId);
  }

  public function existingRole(string $roleId): RoleId
  {
    $role = $this->roles->get(RoleId::fromString($roleId));
    if ($role === null) {
      throw new NotFoundException('Role não encontrada.');
    }

    return $role->id();
  }
}

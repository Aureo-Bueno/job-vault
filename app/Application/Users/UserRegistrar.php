<?php

namespace App\Application\Users;

use App\Application\Exceptions\NotFoundException;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Exception\AlreadyExistsException;
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
 * Registration policy shared by self sign-up (candidate or company) and the admin panel.
 */
final class UserRegistrar
{
  public function __construct(
    private UserRepositoryInterface $users,
    private RoleRepositoryInterface $roles,
    private ?CompanyRepositoryInterface $companies = null
  ) {
  }

  public function register(
    string $name,
    string $email,
    string $password,
    ?string $roleId = null,
    ?string $companyId = null
  ): User {
    $personName = PersonName::fromString($name);
    $emailAddress = EmailAddress::fromString($email);
    $plainPassword = PlainPassword::fromString($password);

    $this->ensureEmailIsAvailable($emailAddress);

    $user = User::register(
      UserId::generate(),
      $personName,
      $emailAddress,
      $plainPassword,
      $this->resolveRole($roleId),
      $this->resolveCompany($companyId)
    );
    $this->users->save($user);

    return $user;
  }

  /**
   * Self sign-up as a Company: creates the company record and links the new user with the recruiter ('gestor') role.
   */
  public function registerCompanyAccount(
    string $personName,
    string $email,
    string $password,
    string $companyName,
    string $companySector = 'Tecnologia',
    string $companyWebsite = '',
    string $companyDescription = ''
  ): User {
    $validPersonName = PersonName::fromString($personName);
    $emailAddress = EmailAddress::fromString($email);
    $plainPassword = PlainPassword::fromString($password);
    $validCompanyName = CompanyName::fromString($companyName);

    $this->ensureEmailIsAvailable($emailAddress);

    if ($this->companies !== null && $this->companies->findByName($validCompanyName) !== null) {
      throw new AlreadyExistsException('Já existe uma empresa cadastrada com este nome.');
    }

    $company = Company::register(
      CompanyId::generate(),
      $validCompanyName,
      $companySector,
      $companyWebsite,
      $companyDescription
    );
    $this->companies?->save($company);

    $recruiterRoleId = $this->roles->findByName(RoleName::recruiter())?->id()
      ?? $this->roles->findByName(RoleName::defaultUser())?->id();

    $user = User::register(
      UserId::generate(),
      $validPersonName,
      $emailAddress,
      $plainPassword,
      $recruiterRoleId,
      $company->id()
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

  public function resolveCompany(?string $companyId): ?CompanyId
  {
    if ($companyId === null || trim($companyId) === '') {
      return null;
    }

    return $this->existingCompany($companyId);
  }

  public function existingCompany(string $companyId): CompanyId
  {
    $id = CompanyId::fromString($companyId);
    if ($this->companies !== null && $this->companies->get($id) === null) {
      throw new NotFoundException('Empresa não encontrada.');
    }

    return $id;
  }
}

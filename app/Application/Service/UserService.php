<?php

namespace App\Application\Service;

use App\Domain\Entity\UserAccount;
use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\EmailAddress;

/**
 * Provides user-oriented application use cases.
 */
class UserService
{
  private UserRepositoryInterface $userRepository;

  public function __construct(UserRepositoryInterface $userRepository)
  {
    $this->userRepository = $userRepository;
  }

  /**
   * Lists users with optional filtering, sorting and pagination.
   *
   * @return User[]
   */
  public function list(
    ?string $where = null,
    ?string $order = null,
    ?string $limit = null,
    array $params = []
  ): array
  {
    return $this->userRepository->findAll($where, $order, $limit, $params);
  }

  /**
   * Counts users using optional criteria.
   */
  public function count(?string $where = null, array $params = []): int
  {
    return $this->userRepository->count($where, $params);
  }

  /**
   * Fetches a user by identifier.
   */
  public function getById(string $id): ?User
  {
    return $this->userRepository->findById($id);
  }

  /**
   * Fetches a user by e-mail after value-object validation.
   */
  public function getByEmail(string $email): ?User
  {
    $emailAddress = EmailAddress::fromString($email);
    if (!$emailAddress) {
      return null;
    }

    return $this->userRepository->findByEmail((string) $emailAddress);
  }

  /**
   * Creates a user from domain entity rules and persists it.
   */
  public function create(User $user, string $plainPassword): ?User
  {
    $newUserData = UserAccount::register(
      $user->name,
      $user->email,
      $plainPassword,
      $user->roleId
    );
    $newUserEntity = $newUserData['entity'] ?? null;
    if (!$newUserEntity) {
      return null;
    }

    $id = $this->userRepository->create($newUserEntity->toModel());
    if (!$id) {
      return null;
    }

    $newUserEntity->setId($id);
    return $newUserEntity->toModel();
  }

  /**
   * Updates user profile and optional password using domain validations.
   */
  public function update(User $user, ?string $plainPassword = null): bool
  {
    $existingUserEntity = UserAccount::restore($user);
    if (!$existingUserEntity) {
      return false;
    }

    if (!$existingUserEntity->applyProfile($user->name, $user->email, $user->roleId)) {
      return false;
    }

    if ($plainPassword !== null && $plainPassword !== '') {
      if (!$existingUserEntity->applyPassword($plainPassword)) {
        return false;
      }
    }

    return $this->userRepository->update($existingUserEntity->toModel());
  }

  /**
   * Deletes a user by identifier.
   */
  public function delete(string $id): bool
  {
    return $this->userRepository->delete($id);
  }
}

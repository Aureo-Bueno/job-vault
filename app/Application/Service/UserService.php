<?php

namespace App\Application\Service;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;

class UserService
{
  private UserRepositoryInterface $userRepository;

  public function __construct(UserRepositoryInterface $userRepository)
  {
    $this->userRepository = $userRepository;
  }

  /** @return User[] */
  public function list(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    return $this->userRepository->findAll($where, $order, $limit);
  }

  public function count(?string $where = null): int
  {
    return $this->userRepository->count($where);
  }

  public function getById(string $id): ?User
  {
    return $this->userRepository->findById($id);
  }

  public function getByEmail(string $email): ?User
  {
    return $this->userRepository->findByEmail($email);
  }

  public function create(User $user, string $plainPassword): ?User
  {
    $user->password = password_hash($plainPassword, PASSWORD_DEFAULT);
    $id = $this->userRepository->create($user);
    if (!$id) {
      return null;
    }

    $user->id = $id;
    return $user;
  }

  public function update(User $user, ?string $plainPassword = null): bool
  {
    if ($plainPassword !== null && $plainPassword !== '') {
      $user->password = password_hash($plainPassword, PASSWORD_DEFAULT);
      return $this->userRepository->update($user);
    }

    return $this->userRepository->update($user);
  }

  public function delete(string $id): bool
  {
    return $this->userRepository->delete($id);
  }
}

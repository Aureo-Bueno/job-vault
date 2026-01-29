<?php

namespace App\Domain\Repository;

use App\Domain\Model\Usuario;

interface UsuarioRepositoryInterface
{
  public function findByEmail(string $email): ?Usuario;

  public function findById(int $id): ?Usuario;

  /** @return Usuario[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array;

  public function count(?string $where = null): int;

  public function create(Usuario $usuario): ?int;

  public function update(Usuario $usuario): bool;

  public function delete(int $id): bool;

  public function getDefaultRoleId(): ?int;
}

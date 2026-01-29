<?php

namespace App\Domain\Repository;

use App\Domain\Model\Vaga;

interface VagaRepositoryInterface
{
  /** @return Vaga[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array;

  public function findById(int $id): ?Vaga;

  public function count(?string $where = null): int;

  public function create(Vaga $vaga): Vaga;

  public function update(Vaga $vaga): bool;

  public function delete(int $id): bool;
}

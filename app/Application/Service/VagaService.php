<?php

namespace App\Application\Service;

use App\Domain\Model\Vaga;
use App\Domain\Repository\VagaRepositoryInterface;

class VagaService
{
  private VagaRepositoryInterface $vagaRepository;

  public function __construct(VagaRepositoryInterface $vagaRepository)
  {
    $this->vagaRepository = $vagaRepository;
  }

  /** @return Vaga[] */
  public function list(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    return $this->vagaRepository->findAll($where, $order, $limit);
  }

  public function count(?string $where = null): int
  {
    return $this->vagaRepository->count($where);
  }

  public function getById(int $id): ?Vaga
  {
    return $this->vagaRepository->findById($id);
  }

  public function create(Vaga $vaga): Vaga
  {
    if ($vaga->data === '') {
      $vaga->data = date('Y-m-d H:i:s');
    }

    return $this->vagaRepository->create($vaga);
  }

  public function update(Vaga $vaga): bool
  {
    return $this->vagaRepository->update($vaga);
  }

  public function delete(int $id): bool
  {
    return $this->vagaRepository->delete($id);
  }
}

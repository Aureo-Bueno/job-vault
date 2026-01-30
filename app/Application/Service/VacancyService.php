<?php

namespace App\Application\Service;

use App\Domain\Model\Vacancy;
use App\Domain\Repository\VacancyRepositoryInterface;

class VacancyService
{
  private VacancyRepositoryInterface $vacancyRepository;

  public function __construct(VacancyRepositoryInterface $vacancyRepository)
  {
    $this->vacancyRepository = $vacancyRepository;
  }

  /** @return Vacancy[] */
  public function list(?string $where = null, ?string $order = null, ?string $limit = null): array
  {
    return $this->vacancyRepository->findAll($where, $order, $limit);
  }

  public function count(?string $where = null): int
  {
    return $this->vacancyRepository->count($where);
  }

  public function getById(string $id): ?Vacancy
  {
    return $this->vacancyRepository->findById($id);
  }

  public function create(Vacancy $vacancy): Vacancy
  {
    if ($vacancy->createdAt === '') {
      $vacancy->createdAt = date('Y-m-d H:i:s');
    }

    return $this->vacancyRepository->create($vacancy);
  }

  public function update(Vacancy $vacancy): bool
  {
    return $this->vacancyRepository->update($vacancy);
  }

  public function delete(string $id): bool
  {
    return $this->vacancyRepository->delete($id);
  }
}

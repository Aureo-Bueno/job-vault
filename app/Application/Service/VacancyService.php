<?php

namespace App\Application\Service;

use App\Domain\Entity\VacancyPosting;
use App\Domain\Model\Vacancy;
use App\Domain\Repository\VacancyRepositoryInterface;

/**
 * Provides vacancy management use cases.
 */
class VacancyService
{
  private VacancyRepositoryInterface $vacancyRepository;

  public function __construct(VacancyRepositoryInterface $vacancyRepository)
  {
    $this->vacancyRepository = $vacancyRepository;
  }

  /**
   * Lists vacancies with optional filtering, ordering and pagination.
   *
   * @return Vacancy[]
   */
  public function list(
    ?string $where = null,
    ?string $order = null,
    ?string $limit = null,
    array $params = []
  ): array
  {
    return $this->vacancyRepository->findAll($where, $order, $limit, $params);
  }

  /**
   * Counts vacancies using optional criteria.
   */
  public function count(?string $where = null, array $params = []): int
  {
    return $this->vacancyRepository->count($where, $params);
  }

  /**
   * Fetches a vacancy by identifier.
   */
  public function getById(string $id): ?Vacancy
  {
    return $this->vacancyRepository->findById($id);
  }

  /**
   * Creates a vacancy after normalizing input through the domain entity.
   */
  public function create(Vacancy $vacancy): Vacancy
  {
    $vacancyEntity = VacancyPosting::fromModel($vacancy);
    $normalizedVacancy = $vacancyEntity->toModel();

    return $this->vacancyRepository->create($normalizedVacancy);
  }

  /**
   * Updates a vacancy after normalizing input through the domain entity.
   */
  public function update(Vacancy $vacancy): bool
  {
    $vacancyEntity = VacancyPosting::fromModel($vacancy);
    return $this->vacancyRepository->update($vacancyEntity->toModel());
  }

  /**
   * Deletes a vacancy by identifier.
   */
  public function delete(string $id): bool
  {
    return $this->vacancyRepository->delete($id);
  }
}

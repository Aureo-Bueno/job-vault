<?php

namespace App\Application\Features\Vacancies;

use App\Application\Exceptions\NotFoundException;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;

/**
 * Loads a vacancy or fails with a NotFoundException.
 */
final class VacancyLoader
{
  public static function load(VacancyRepositoryInterface $vacancies, string $id): Vacancy
  {
    $vacancy = $vacancies->get(VacancyId::fromString($id));
    if ($vacancy === null) {
      throw new NotFoundException('Vaga não encontrada.');
    }

    return $vacancy;
  }
}

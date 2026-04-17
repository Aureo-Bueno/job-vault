<?php

namespace App\Application\Mappings;

use App\Application\DTOs\VacancyDto;
use App\Domain\Model\Vacancy;

/**
 * Maps vacancy domain models into application DTOs.
 */
final class VacancyMapping
{
  public static function toDto(Vacancy $vacancy): VacancyDto
  {
    return new VacancyDto(
      $vacancy->id,
      $vacancy->title,
      $vacancy->description,
      $vacancy->isActive,
      $vacancy->createdAt
    );
  }
}

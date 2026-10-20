<?php

namespace App\Application\DTOs;

use App\Domain\Vacancy\Vacancy;

final class VacancyDto
{
  public function __construct(
    public readonly string $id,
    public readonly string $title,
    public readonly string $description,
    public readonly bool $isActive,
    public readonly string $createdAt
  ) {
  }

  public static function fromVacancy(Vacancy $vacancy): self
  {
    return new self(
      $vacancy->id()->value(),
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->isOpen(),
      $vacancy->publishedAt()->format('Y-m-d H:i:s')
    );
  }
}

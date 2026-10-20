<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class GetVacancyByIdHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
  }

  public function queryClass(): string
  {
    return GetVacancyByIdQuery::class;
  }

  public function handle(Query $query): ?VacancyDto
  {
    if (!$query instanceof GetVacancyByIdQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    if (!VacancyId::isValid($query->vacancyId)) {
      return null;
    }

    $vacancy = $this->vacancies->get(VacancyId::fromString($query->vacancyId));
    return $vacancy ? VacancyDto::fromVacancy($vacancy) : null;
  }
}

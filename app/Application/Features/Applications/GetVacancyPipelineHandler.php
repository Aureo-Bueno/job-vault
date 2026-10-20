<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\PipelineColumnDto;
use App\Application\DTOs\VacancyApplicantDto;
use App\Application\DTOs\VacancyPipelineDto;
use App\Application\Queries\Applications\GetVacancyPipelineQuery;
use App\Application\Queries\Applications\ListVacancyApplicantsQuery;
use App\Domain\JobApplication\ApplicationStatus;
use InvalidArgumentException;

/**
 * Groups the vacancy applicants (already ranked by match) into board columns.
 * Access control (company scope) is enforced by the wrapped ListVacancyApplicantsHandler.
 */
final class GetVacancyPipelineHandler implements QueryHandlerInterface
{
  public function __construct(private ListVacancyApplicantsHandler $applicants)
  {
  }

  public function queryClass(): string
  {
    return GetVacancyPipelineQuery::class;
  }

  public function handle(Query $query): VacancyPipelineDto
  {
    if (!$query instanceof GetVacancyPipelineQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $applicants = $this->applicants->handle(new ListVacancyApplicantsQuery($query->vacancyId));

    $byStatus = [];
    foreach ($applicants as $applicant) {
      $byStatus[$applicant->status][] = $applicant;
    }

    $columns = array_map(
      static fn (ApplicationStatus $status): PipelineColumnDto => new PipelineColumnDto(
        $status->value,
        $status->label(),
        $byStatus[$status->value] ?? []
      ),
      ApplicationStatus::cases()
    );

    $inProcess = count(array_filter(
      $applicants,
      static fn (VacancyApplicantDto $a): bool => ApplicationStatus::fromStored($a->status)->isInProcess()
    ));

    return new VacancyPipelineDto($columns, count($applicants), $inProcess);
  }
}

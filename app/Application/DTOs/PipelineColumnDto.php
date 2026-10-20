<?php

namespace App\Application\DTOs;

/**
 * One column of the pipeline board.
 */
final class PipelineColumnDto
{
  /**
   * @param list<VacancyApplicantDto> $applicants best match first
   */
  public function __construct(
    public readonly string $status,
    public readonly string $label,
    public readonly array $applicants
  ) {
  }

  public function count(): int
  {
    return count($this->applicants);
  }
}

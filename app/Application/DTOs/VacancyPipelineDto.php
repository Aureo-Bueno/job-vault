<?php

namespace App\Application\DTOs;

/**
 * Hiring pipeline of a vacancy.
 */
final class VacancyPipelineDto
{
  /**
   * @param list<PipelineColumnDto> $columns in pipeline order
   */
  public function __construct(
    public readonly array $columns,
    public readonly int $total,
    public readonly int $inProcess
  ) {
  }
}

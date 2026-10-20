<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Open vacancies (within the deadline) for candidates. Unknown filter values are ignored.
 *
 * `search` is a full-text search (title, description, topics, benefits). Sort:
 * - relevance: text relevance when searching, otherwise the candidate's match;
 * - match: candidate's match first;
 * - recent: newest first.
 */
final class ListOpenVacanciesQuery implements Query
{
  public function __construct(
    public readonly ?string $search = null,
    public readonly ?string $area = null,
    public readonly ?string $workMode = null,
    public readonly string $sort = 'relevance',
    public readonly ?int $minSalary = null,
    public readonly ?string $state = null,
    public readonly ?string $contractType = null
  ) {
  }
}

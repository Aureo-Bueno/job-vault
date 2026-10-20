<?php

namespace Tests\Support;

use App\Domain\Shared\PageRequest;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Infrastructure\Persistence\Search\FullTextTokenizer;
use App\Infrastructure\Persistence\Search\VacancySearchDocument;
use DateTimeImmutable;

final class InMemoryVacancyRepository implements VacancyRepositoryInterface
{
  /** @var array<string,Vacancy> */
  private array $vacancies = [];

  public function get(VacancyId $id): ?Vacancy
  {
    return isset($this->vacancies[$id->value()]) ? clone $this->vacancies[$id->value()] : null;
  }

  public function matching(VacancyFilter $filter, PageRequest $page): array
  {
    $matches = array_values(array_filter($this->vacancies, static function (Vacancy $vacancy) use ($filter): bool {
      if ($filter->text !== null && self::relevance($filter, $vacancy) === 0) {
        return false;
      }

      if ($filter->status !== null && $vacancy->status() !== $filter->status) {
        return false;
      }

      if ($filter->companyId !== null && ($vacancy->companyId() === null || !$vacancy->companyId()->equals($filter->companyId))) {
        return false;
      }

      if ($filter->area !== null && $vacancy->area() !== $filter->area) {
        return false;
      }

      if ($filter->workMode !== null && $vacancy->workMode() !== $filter->workMode) {
        return false;
      }

      $terms = $vacancy->terms();
      if ($filter->minSalary !== null && !$terms->salary()->reaches($filter->minSalary)) {
        return false;
      }

      if ($filter->state !== null && $terms->location()?->state() !== $filter->state) {
        return false;
      }

      if ($filter->contractType !== null && $terms->contractType() !== $filter->contractType) {
        return false;
      }

      if ($filter->acceptingApplicationsAt !== null && ($vacancy->closesAt()?->hasPassed($filter->acceptingApplicationsAt) ?? false)) {
        return false;
      }

      return true;
    }));

    usort(
      $matches,
      static fn (Vacancy $a, Vacancy $b): int => (self::relevance($filter, $b) <=> self::relevance($filter, $a))
        ?: ($b->publishedAt() <=> $a->publishedAt())
    );

    return array_map(static fn (Vacancy $v): Vacancy => clone $v, array_slice($matches, $page->offset(), $page->size));
  }

  public function countMatching(VacancyFilter $filter): int
  {
    return count($this->matching($filter, PageRequest::all()));
  }

  public function openPastDeadline(DateTimeImmutable $now): array
  {
    $expired = array_filter(
      $this->vacancies,
      static fn (Vacancy $v): bool => $v->isOpen() && ($v->closesAt()?->hasPassed($now) ?? false)
    );

    return array_map(static fn (Vacancy $v): Vacancy => clone $v, array_values($expired));
  }

  /**
   * Same rules as the MySQL BOOLEAN MODE query: every word must prefix-match a token of the
   * search document; the score is how many tokens matched (title/topics are repeated in it).
   * 1 when there is no text search (or nothing searchable), 0 when a word is missing.
   */
  private static function relevance(VacancyFilter $filter, Vacancy $vacancy): int
  {
    $words = $filter->text !== null ? FullTextTokenizer::queryWords($filter->text) : [];
    if ($words === []) {
      return 1;
    }

    $tokens = explode(' ', VacancySearchDocument::forVacancy($vacancy));
    $score = 0;
    foreach ($words as $word) {
      $hits = count(array_filter($tokens, static fn (string $t): bool => str_starts_with($t, $word)));
      if ($hits === 0) {
        return 0;
      }
      $score += $hits;
    }

    return $score;
  }

  public function save(Vacancy $vacancy): void
  {
    $this->vacancies[$vacancy->id()->value()] = clone $vacancy;
  }

  public function remove(VacancyId $id): void
  {
    unset($this->vacancies[$id->value()]);
  }
}

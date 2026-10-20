<?php

namespace App\Infrastructure\Persistence\Search;

use App\Domain\Vacancy\Vacancy;

/**
 * Text indexed for the vacancy full-text search (`vacancies.search_document`).
 *
 * Weights come from repetition (InnoDB ranks by term frequency): title x3,
 * topics x2, benefits and description x1.
 */
final class VacancySearchDocument
{
  private const TITLE_WEIGHT = 3;
  private const TOPIC_WEIGHT = 2;

  private function __construct()
  {
  }

  public static function forVacancy(Vacancy $vacancy): string
  {
    return self::build(
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->topicLabels(),
      $vacancy->terms()->benefitLabels()
    );
  }

  /**
   * @param list<string> $topics
   * @param list<string> $benefits
   */
  public static function build(string $title, string $description, array $topics, array $benefits): string
  {
    $topicText = implode(' ', $topics);
    $parts = [
      ...array_fill(0, self::TITLE_WEIGHT, $title),
      ...array_fill(0, self::TOPIC_WEIGHT, $topicText),
      implode(' ', $benefits),
      $description,
    ];

    return implode(' ', FullTextTokenizer::tokens(implode(' ', $parts)));
  }
}

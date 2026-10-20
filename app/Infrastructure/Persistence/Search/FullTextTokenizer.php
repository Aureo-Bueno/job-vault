<?php

namespace App\Infrastructure\Persistence\Search;

use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\Slug;

/**
 * Normalizes text for the InnoDB FULLTEXT index and builds BOOLEAN MODE queries.
 * Documents and queries go through the same rules, so they always agree:
 *
 * - lowercase without accents (the index ignores the `ai_ci` collation);
 * - tech names keep their meaning: "C#" -> csharp, "C++" -> cplusplus, ".NET" -> dotnet, "Node.js" -> nodejs;
 * - Portuguese stopwords are dropped (otherwise "vaga de PHP" would require the word "de");
 * - tokens shorter than innodb_ft_min_token_size (3) are padded with "_" ("go" -> "go_")
 *   so they are indexed; the query prefix "go*" still finds them.
 */
final class FullTextTokenizer
{
  /** innodb_ft_min_token_size (MySQL default). */
  public const MIN_TOKEN_LENGTH = 3;

  public const MAX_QUERY_TOKENS = 8;

  private const STOPWORDS = [
    'a', 'o', 'as', 'os', 'e', 'de', 'da', 'do', 'das', 'dos', 'em', 'no', 'na', 'nos', 'nas',
    'um', 'uma', 'uns', 'umas', 'com', 'para', 'pra', 'por', 'ou', 'ao', 'aos', 'que', 'se',
  ];

  /** Applied (in order) after accents are removed. */
  private const ALIASES = [
    '/(?<![a-z0-9])c#/' => ' csharp ',
    '/(?<![a-z0-9])f#/' => ' fsharp ',
    '/(?<![a-z0-9])c\+\+/' => ' cplusplus ',
    '/\.net(?![a-z0-9])/' => ' dotnet ',
    '/([a-z0-9])\.js(?![a-z0-9])/' => '$1js',
  ];

  private function __construct()
  {
  }

  /**
   * Every token of the text, in order and with repetitions (repetitions raise the relevance).
   *
   * @return list<string>
   */
  public static function tokens(string $text): array
  {
    $folded = Slug::fold($text);
    $folded = preg_replace(array_keys(self::ALIASES), array_values(self::ALIASES), $folded) ?? $folded;

    $stopwords = array_flip(self::STOPWORDS);
    $tokens = [];
    foreach (preg_split('/[^a-z0-9]+/', $folded, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
      if (!isset($stopwords[$token])) {
        $tokens[] = str_pad($token, self::MIN_TOKEN_LENGTH, '_');
      }
    }

    return $tokens;
  }

  /**
   * Unique query words (padding removed), at most MAX_QUERY_TOKENS.
   *
   * @return list<string>
   */
  public static function queryWords(SearchTerm|string $term): array
  {
    $words = array_map(static fn (string $t): string => rtrim($t, '_'), self::tokens((string) $term));

    return array_slice(array_values(array_unique($words)), 0, self::MAX_QUERY_TOKENS);
  }

  /**
   * "+php* +senior*": every word is required and matches as a prefix (search while typing).
   * Null when nothing searchable is left (only stopwords or punctuation).
   */
  public static function booleanQuery(SearchTerm|string $term): ?string
  {
    $words = self::queryWords($term);

    return $words === [] ? null : implode(' ', array_map(static fn (string $w): string => '+' . $w . '*', $words));
  }
}

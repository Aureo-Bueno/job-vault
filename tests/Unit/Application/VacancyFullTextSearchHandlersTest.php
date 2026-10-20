<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\DTOs\VacancyDto;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\ListOpenVacanciesHandler;
use App\Application\Features\Vacancies\SearchVacanciesHandler;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryVacancyRepository;

/**
 * Search semantics with the in-memory repository (same rules as the MySQL FULLTEXT query;
 * the real index is covered by Tests\Integration\Persistence\VacancyFullTextSearchIntegrationTest).
 */
final class VacancyFullTextSearchHandlersTest extends TestCase
{
  private InMemoryVacancyRepository $vacancies;

  protected function setUp(): void
  {
    $this->vacancies = new InMemoryVacancyRepository();
    $this->create('Dev PHP Sênior', 'APIs REST e microsserviços.', ['PHP', 'Docker'], 'Plano de saúde');
    $this->create('Analista de Dados', 'Pipelines em Python. Desejável conhecer PHP.', ['Python', 'SQL']);
    $this->create('Dev C#', 'Aplicações .NET.', ['C#', '.NET'], 'Vale-refeição');
    $this->create('Dev Go', 'Serviços de alta performance.', ['Go'], status: 'rascunho');
  }

  public function testSearchesTitleDescriptionTopicsAndBenefits(): void
  {
    $this->assertSame(['Dev PHP Sênior'], $this->open('microsservicos'));
    $this->assertSame(['Dev PHP Sênior'], $this->open('docker'));
    $this->assertSame(['Dev C#'], $this->open('refeição'));
    $this->assertSame(['Dev PHP Sênior'], $this->open('plano saude'));
  }

  public function testEveryWordIsRequiredAcrossFields(): void
  {
    $this->assertSame(['Dev PHP Sênior'], $this->open('php docker'));
    $this->assertSame([], $this->open('php refeição'));
  }

  public function testTitleMatchesRankAboveDescriptionMatches(): void
  {
    $this->assertSame(['Dev PHP Sênior', 'Analista de Dados'], $this->open('php'));
  }

  public function testPrefixAccentsAndTechNames(): void
  {
    $this->assertSame(['Dev PHP Sênior'], $this->open('seni'));
    $this->assertSame(['Dev C#'], $this->open('c#'));
    $this->assertSame(['Dev C#'], $this->open('.net'));
  }

  public function testOnlyStopwordsDoesNotFilter(): void
  {
    $this->assertCount(3, $this->open('de'));
  }

  public function testCompanyPanelSearchIncludesDraftsAndRanks(): void
  {
    $result = (new SearchVacanciesHandler($this->vacancies))->handle(new SearchVacanciesQuery('serviços'));

    // Drafts are visible to the company. Prefixes match from the start of a word: "microsserviços" is not "serviços".
    $this->assertSame(['Dev Go'], array_map(static fn (VacancyDto $v): string => $v->title, $result->items));
    $this->assertSame(1, $result->total);
  }

  /**
   * @return list<string>
   */
  private function open(string $search, string $sort = 'relevance'): array
  {
    $items = (new ListOpenVacanciesHandler($this->vacancies))->handle(new ListOpenVacanciesQuery($search, sort: $sort));

    return array_map(static fn (VacancyDto $v): string => $v->title, $items);
  }

  /**
   * @param list<string> $topics
   */
  private function create(string $title, string $description, array $topics, string $benefits = '', string $status = 'aberta'): void
  {
    (new CreateVacancyHandler($this->vacancies))->handle(new CreateVacancyCommand(
      $title,
      $description,
      $status,
      topics: $topics,
      benefits: $benefits
    ));
  }
}

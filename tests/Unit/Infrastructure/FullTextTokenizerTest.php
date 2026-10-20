<?php

namespace Tests\Unit\Infrastructure;

use App\Domain\Shared\SearchTerm;
use App\Infrastructure\Persistence\Search\FullTextTokenizer;
use App\Infrastructure\Persistence\Search\VacancySearchDocument;
use PHPUnit\Framework\TestCase;

final class FullTextTokenizerTest extends TestCase
{
  public function testFoldsAccentsAndCase(): void
  {
    $this->assertSame(['programacao', 'avancada'], FullTextTokenizer::tokens('Programação AVANÇADA'));
  }

  public function testKeepsTechNames(): void
  {
    $this->assertSame(
      ['csharp', 'cplusplus', 'dotnet', 'nodejs', 'vuejs', 'asp', 'dotnet'],
      FullTextTokenizer::tokens('C#, C++, .NET, Node.js, Vue.js e ASP.NET')
    );
  }

  public function testDropsPortugueseStopwordsAndPadsShortTokens(): void
  {
    $this->assertSame(['vaga', 'go_', 'ui_', 'r__'], FullTextTokenizer::tokens('Vaga de Go com UI e R'));
  }

  public function testBooleanQueryRequiresEveryWordAsPrefix(): void
  {
    $this->assertSame('+php* +senior*', FullTextTokenizer::booleanQuery(SearchTerm::tryFrom('PHP Sênior')));
    $this->assertSame('+go*', FullTextTokenizer::booleanQuery('go'));
    $this->assertSame('+csharp*', FullTextTokenizer::booleanQuery('c#'));
  }

  public function testBooleanOperatorsTypedByTheUserAreNeutralized(): void
  {
    $this->assertSame('+vale* +refeicao*', FullTextTokenizer::booleanQuery('vale-refeição'));
    $this->assertSame('+php* +mysql*', FullTextTokenizer::booleanQuery('"php" -mysql* (~>'));
  }

  public function testQueryWithOnlyStopwordsOrPunctuationIsNull(): void
  {
    $this->assertNull(FullTextTokenizer::booleanQuery('de da do'));
    $this->assertNull(FullTextTokenizer::booleanQuery('?!%'));
  }

  public function testQueryKeepsAtMostEightUniqueWords(): void
  {
    $this->assertCount(FullTextTokenizer::MAX_QUERY_TOKENS, FullTextTokenizer::queryWords('a1 b2 c3 d4 e5 f6 g7 h8 i9 j10 a1'));
  }

  public function testDocumentWeightsTitleAndTopics(): void
  {
    $document = VacancySearchDocument::build('Dev PHP', 'APIs REST', ['Docker'], ['Plano de saúde']);
    $tokens = explode(' ', $document);

    $this->assertSame(3, count(array_keys($tokens, 'php', true)));
    $this->assertSame(2, count(array_keys($tokens, 'docker', true)));
    $this->assertContains('saude', $tokens);
    $this->assertContains('apis', $tokens);
  }
}

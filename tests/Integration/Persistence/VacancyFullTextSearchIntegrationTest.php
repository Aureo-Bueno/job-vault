<?php

namespace Tests\Integration\Persistence;

use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\EmploymentTerms;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoVacancyRepository;
use PHPUnit\Framework\TestCase;

/**
 * MySQL FULLTEXT search for vacancies.
 *
 * InnoDB applies full-text index changes only on COMMIT, so unlike the other integration
 * tests this one writes committed rows and deletes them in tearDown. Every vacancy carries a
 * random marker word so the assertions ignore the seeded demo data.
 */
final class VacancyFullTextSearchIntegrationTest extends TestCase
{
  private Database $db;
  private PdoVacancyRepository $repository;
  private string $marker;

  /** @var array<string,VacancyId> title => id */
  private array $ids = [];

  protected function setUp(): void
  {
    if (getenv('RUN_INTEGRATION') !== '1') {
      $this->markTestSkipped('Integration tests disabled. Set RUN_INTEGRATION=1 to enable.');
    }

    $this->db = Database::fromEnvironment();
    $this->repository = new PdoVacancyRepository($this->db);
    $this->marker = 'zqx' . bin2hex(random_bytes(4));

    $this->add('Desenvolvedor PHP Sênior', 'APIs REST e microsserviços. Programação orientada a objetos.', ['PHP', 'Docker'], 'Plano de saúde');
    $this->add('Analista de Dados', 'Pipelines em Python; desejável PHP. Talk about data.', ['Python', 'SQL']);
    $this->add('Dev C#', 'Aplicações .NET com Azure.', ['C#', '.NET'], 'Vale-refeição');
    $this->add('Dev Go', 'Serviços de alta performance.', ['Go']);
    $this->add('Tech Lead PHP', 'Liderança técnica.', ['PHP'], status: VacancyStatus::Draft);
  }

  protected function tearDown(): void
  {
    foreach ($this->ids as $id) {
      $this->repository->remove($id);
    }
  }

  public function testSearchesAllFieldsIgnoringAccents(): void
  {
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('microsservicos'));
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('programação'));
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('docker'));
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('plano de saude'));
    $this->assertSame(['Dev C#'], $this->open('vale-refeição'));
  }

  public function testEveryWordIsRequiredAndMatchesAsPrefix(): void
  {
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('php docker'));
    $this->assertSame([], $this->open('php refeição'));
    $this->assertSame(['Desenvolvedor PHP Sênior'], $this->open('desenv seni'));
  }

  public function testTechNamesShortWordsAndEnglishWords(): void
  {
    $this->assertSame(['Dev C#'], $this->open('c#'));
    $this->assertSame(['Dev C#'], $this->open('.net'));
    $this->assertSame(['Dev Go'], $this->open('go'));
    // "about" is in InnoDB's default stopword list; the index is created without it.
    $this->assertSame(['Analista de Dados'], $this->open('about'));
  }

  public function testTitleMatchesRankFirst(): void
  {
    $this->assertSame(['Desenvolvedor PHP Sênior', 'Analista de Dados'], $this->open('php'));
  }

  public function testCompanyPanelFindsDraftsAndCounts(): void
  {
    $filter = new VacancyFilter(SearchTerm::tryFrom('php ' . $this->marker));

    $this->assertSame(3, $this->repository->countMatching($filter));
    $titles = array_map(static fn (Vacancy $v): string => $v->title()->value(), $this->repository->matching($filter, PageRequest::all()));
    // The draft is included. Both "PHP in the title" vacancies tie on relevance; the description-only match comes last.
    $this->assertContains('Tech Lead PHP', $titles);
    $this->assertSame('Analista de Dados', $titles[2]);
  }

  public function testIndexFollowsUpdates(): void
  {
    $vacancy = $this->repository->get($this->ids['Dev Go']);
    $vacancy?->revise(VacancyTitle::fromString('Dev Rust'), VacancyDescription::fromString('Sistemas embarcados. ' . $this->marker), topics: ['Rust']);
    $this->repository->save($vacancy);

    $this->assertSame([], $this->open('go'));
    $this->assertSame(['Dev Rust'], $this->open('rust embarcados'));
  }

  public function testOnlyStopwordsDoesNotFilter(): void
  {
    $all = $this->repository->countMatching(new VacancyFilter());

    $this->assertSame($all, $this->repository->countMatching(new VacancyFilter(SearchTerm::tryFrom('de da'))));
  }

  /**
   * Titles of open vacancies found by the search (restricted to this test's rows), in relevance order.
   *
   * @return list<string>
   */
  private function open(string $search): array
  {
    $found = $this->repository->matching(VacancyFilter::openOnly(SearchTerm::tryFrom($search . ' ' . $this->marker)), PageRequest::all());

    return array_map(static fn (Vacancy $v): string => $v->title()->value(), $found);
  }

  /**
   * @param list<string> $topics
   */
  private function add(string $title, string $description, array $topics, string $benefits = '', VacancyStatus $status = VacancyStatus::Open): void
  {
    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString($title),
      VacancyDescription::fromString($description . ' ' . $this->marker),
      $status,
      null,
      null,
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      $topics,
      null,
      EmploymentTerms::of(benefits: $benefits)
    );
    $this->repository->save($vacancy);
    $this->ids[$title] = $vacancy->id();
  }
}

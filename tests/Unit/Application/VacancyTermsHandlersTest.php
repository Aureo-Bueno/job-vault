<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Vacancies\CloseExpiredVacanciesCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\DTOs\VacancyDto;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Vacancies\CloseExpiredVacanciesHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\GetVacancyByIdHandler;
use App\Application\Features\Vacancies\ListOpenVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyDeadline;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyNotOpenException;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryJobApplicationRepository;
use Tests\Support\InMemoryVacancyRepository;

final class VacancyTermsHandlersTest extends TestCase
{
  private InMemoryVacancyRepository $vacancies;

  protected function setUp(): void
  {
    $this->vacancies = new InMemoryVacancyRepository();
  }

  public function testCreateWithEmploymentTermsAndDeadline(): void
  {
    $deadline = $this->daysFromToday(10);
    $dto = $this->create(
      'Dev PHP',
      workMode: 'hibrido',
      contractType: 'pj',
      salaryMin: 9000,
      salaryMax: 12000,
      city: 'Curitiba',
      state: 'pr',
      benefits: 'Plano de saúde, PLR',
      closesAt: $deadline
    );

    $this->assertSame('pj', $dto->contractType);
    $this->assertSame('PJ', $dto->contractTypeLabel);
    $this->assertSame('R$ 9.000 – R$ 12.000', $dto->salaryLabel);
    $this->assertSame('Curitiba/PR', $dto->locationLabel);
    $this->assertSame('PR', $dto->state);
    $this->assertSame(['Plano de saúde', 'PLR'], $dto->benefits);
    $this->assertSame($deadline, $dto->closesAt);
    $this->assertTrue($dto->acceptsApplications);

    $loaded = (new GetVacancyByIdHandler($this->vacancies))->handle(new GetVacancyByIdQuery($dto->id));
    $this->assertSame('R$ 9.000 – R$ 12.000', $loaded?->salaryLabel);
  }

  public function testDefaultsAreCltAndNegotiableSalary(): void
  {
    $dto = $this->create('Dev PHP');

    $this->assertSame('CLT', $dto->contractTypeLabel);
    $this->assertSame('A combinar', $dto->salaryLabel);
    $this->assertTrue($dto->isSalaryNegotiable());
    $this->assertSame('', $dto->locationLabel);
    $this->assertFalse($dto->hasDeadline());
  }

  public function testOnSiteVacancyWithoutCityIsRejected(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->create('Dev PHP', workMode: 'presencial');
  }

  public function testInvalidContractTypeIsRejected(): void
  {
    $this->expectException(InvalidValueException::class);
    $this->create('Dev PHP', contractType: 'cooperado');
  }

  public function testPastDeadlineIsRejectedOnCreate(): void
  {
    $this->expectException(BusinessRuleViolationException::class);
    $this->create('Dev PHP', closesAt: $this->daysFromToday(-1));
  }

  public function testUpdateKeepsTermsUnlessContractTypeIsGiven(): void
  {
    $dto = $this->create('Dev PHP', contractType: 'pj', salaryMin: 9000, benefits: 'PLR', closesAt: $this->daysFromToday(5));
    $update = new UpdateVacancyHandler($this->vacancies);

    $update->handle(new UpdateVacancyCommand($dto->id, 'Dev PHP Sr', 'Desc'));
    $kept = $this->vacancies->get(VacancyId::fromString($dto->id));
    $this->assertSame('A partir de R$ 9.000', $kept?->terms()->salary()->label());
    $this->assertSame(['PLR'], $kept?->terms()->benefitLabels());
    $this->assertNotNull($kept?->closesAt());

    $update->handle(new UpdateVacancyCommand($dto->id, 'Dev PHP Sr', 'Desc', contractType: 'clt', salaryMax: 15000, closesAt: ''));
    $replaced = $this->vacancies->get(VacancyId::fromString($dto->id));
    $this->assertSame('CLT', $replaced?->terms()->contractType()->label());
    $this->assertSame('Até R$ 15.000', $replaced?->terms()->salary()->label());
    $this->assertSame([], $replaced?->terms()->benefitLabels());
    $this->assertNull($replaced?->closesAt());
  }

  public function testOpenListFiltersBySalaryStateAndContract(): void
  {
    $this->create('PHP Curitiba', workMode: 'hibrido', salaryMin: 6000, salaryMax: 9000, city: 'Curitiba', state: 'PR');
    $this->create('PHP SP', workMode: 'presencial', contractType: 'pj', salaryMin: 12000, city: 'São Paulo', state: 'SP');
    $this->create('PHP a combinar');
    $this->create('PHP até 5k', salaryMax: 5000);

    $titles = fn (ListOpenVacanciesQuery $q): array => $this->titles((new ListOpenVacanciesHandler($this->vacancies))->handle($q));

    $this->assertSame(['PHP Curitiba', 'PHP SP'], $titles(new ListOpenVacanciesQuery(minSalary: 8000)));
    $this->assertSame(['PHP Curitiba'], $titles(new ListOpenVacanciesQuery(state: 'pr')));
    $this->assertSame(['PHP SP'], $titles(new ListOpenVacanciesQuery(contractType: 'pj')));
    $this->assertCount(4, $titles(new ListOpenVacanciesQuery(state: 'XX', contractType: '???')), 'unknown filter values are ignored');
  }

  public function testOpenListHidesVacanciesPastTheDeadlineBeforeTheJobRuns(): void
  {
    $this->create('No prazo', closesAt: $this->daysFromToday(3));
    $this->vacancies->save($this->expiredOpenVacancy('Prazo vencido'));

    $open = (new ListOpenVacanciesHandler($this->vacancies))->handle(new ListOpenVacanciesQuery());

    $this->assertSame(['No prazo'], $this->titles($open));
  }

  public function testCannotApplyAfterTheDeadline(): void
  {
    $expired = $this->expiredOpenVacancy('Prazo vencido');
    $this->vacancies->save($expired);

    $this->expectException(VacancyNotOpenException::class);
    (new ApplyToVacancyHandler($this->vacancies, new InMemoryJobApplicationRepository(), FakeCurrentUser::as('7')))
      ->handle(new ApplyToVacancyCommand($expired->id()->value()));
  }

  public function testCloseExpiredClosesOnlyOpenVacanciesPastTheDeadline(): void
  {
    $onTime = $this->create('No prazo', closesAt: $this->daysFromToday(3));
    $noDeadline = $this->create('Sem prazo');
    $expired = $this->expiredOpenVacancy('Prazo vencido');
    $this->vacancies->save($expired);
    $expiredDraft = $this->expiredOpenVacancy('Rascunho vencido', VacancyStatus::Draft);
    $this->vacancies->save($expiredDraft);

    $closed = (new CloseExpiredVacanciesHandler($this->vacancies))->handle(new CloseExpiredVacanciesCommand());

    $this->assertSame(['Prazo vencido'], $this->titles($closed));
    $stored = $this->vacancies->get($expired->id());
    $this->assertSame(VacancyStatus::Closed, $stored?->status());
    $this->assertSame(VacancyCloseReason::Deadline, $stored?->closeReason());
    $this->assertTrue($this->vacancies->get(VacancyId::fromString($onTime->id))?->isOpen());
    $this->assertTrue($this->vacancies->get(VacancyId::fromString($noDeadline->id))?->isOpen());
    $this->assertTrue($this->vacancies->get($expiredDraft->id())?->isDraft());

    $this->assertSame([], (new CloseExpiredVacanciesHandler($this->vacancies))->handle(new CloseExpiredVacanciesCommand()), 'idempotent');
  }

  public function testReopeningAVacancyClosedByDeadlineNeedsANewDeadline(): void
  {
    $expired = $this->expiredOpenVacancy('Prazo vencido');
    $this->vacancies->save($expired);
    (new CloseExpiredVacanciesHandler($this->vacancies))->handle(new CloseExpiredVacanciesCommand());
    $update = new UpdateVacancyHandler($this->vacancies);
    $id = $expired->id()->value();

    try {
      $update->handle(new UpdateVacancyCommand($id, 'Prazo vencido', 'Desc', 'aberta'));
      $this->fail('Reopening with the old deadline should be refused.');
    } catch (BusinessRuleViolationException $e) {
      $this->assertStringContainsString('Defina um novo prazo', $e->getMessage());
    }

    $update->handle(new UpdateVacancyCommand($id, 'Prazo vencido', 'Desc', 'aberta', closesAt: $this->daysFromToday(7)));
    $this->assertTrue($this->vacancies->get($expired->id())?->acceptsApplications());
  }

  /**
   * @param string|list<string> $benefits
   */
  private function create(
    string $title,
    string $workMode = 'remoto',
    string $contractType = 'clt',
    ?int $salaryMin = null,
    ?int $salaryMax = null,
    ?string $city = null,
    ?string $state = null,
    string|array $benefits = [],
    ?string $closesAt = null
  ): VacancyDto {
    return (new CreateVacancyHandler($this->vacancies))->handle(new CreateVacancyCommand(
      $title,
      'Descrição',
      'aberta',
      'backend',
      'pleno',
      $workMode,
      ['PHP'],
      null,
      1,
      $contractType,
      $salaryMin,
      $salaryMax,
      $city,
      $state,
      $benefits,
      $closesAt
    ));
  }

  private function expiredOpenVacancy(string $title, VacancyStatus $status = VacancyStatus::Open): Vacancy
  {
    $publishedAt = new DateTimeImmutable('-10 days');

    return Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString($title),
      VacancyDescription::fromString('Desc'),
      $status,
      $publishedAt,
      null,
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      [],
      null,
      null,
      // Valid when it was published 10 days ago; it passed 2 days ago.
      VacancyDeadline::fromString($this->daysFromToday(-2))
    );
  }

  private function daysFromToday(int $days): string
  {
    return (new DateTimeImmutable(VacancyDeadline::today()))->modify(sprintf('%+d days', $days))->format('Y-m-d');
  }

  /**
   * @param list<VacancyDto> $items
   * @return list<string>
   */
  private function titles(array $items): array
  {
    $titles = array_map(static fn (VacancyDto $v): string => $v->title, $items);
    sort($titles);

    return $titles;
  }
}

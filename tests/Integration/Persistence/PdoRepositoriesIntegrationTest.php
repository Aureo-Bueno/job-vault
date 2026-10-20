<?php

namespace Tests\Integration\Persistence;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\EmailAddress;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\User;
use App\Domain\User\UserFilter;
use App\Domain\User\UserId;
use App\Domain\Shared\BrazilianState;
use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\EmploymentTerms;
use App\Domain\Vacancy\SalaryRange;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDeadline;
use App\Domain\Vacancy\VacancyLocation;
use App\Domain\Vacancy\VacancyCloseReason;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyPositions;
use App\Domain\Vacancy\VacancyStatus;
use App\Domain\Vacancy\VacancyTitle;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoCandidateProfileRepository;
use App\Infrastructure\Persistence\PdoCompanyRepository;
use App\Infrastructure\Persistence\PdoJobApplicationRepository;
use App\Infrastructure\Persistence\PdoPermissionRepository;
use App\Infrastructure\Persistence\PdoRoleRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoVacancyRepository;
use PHPUnit\Framework\TestCase;

/**
 * Round-trips every aggregate through MySQL inside a transaction that is rolled back.
 */
final class PdoRepositoriesIntegrationTest extends TestCase
{
  private Database $db;

  protected function setUp(): void
  {
    if (getenv('RUN_INTEGRATION') !== '1') {
      $this->markTestSkipped('Integration tests disabled. Set RUN_INTEGRATION=1 to enable.');
    }

    $this->db = Database::fromEnvironment();
    $this->db->beginTransaction();
  }

  protected function tearDown(): void
  {
    if (isset($this->db)) {
      $this->db->rollBack();
    }
  }

  public function testVacancyRoundTripAndSearch(): void
  {
    $repository = new PdoVacancyRepository($this->db);
    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Integração 100% PHP_dev'),
      VacancyDescription::fromString('Teste'),
      VacancyStatus::Closed
    );
    $repository->save($vacancy);

    $loaded = $repository->get($vacancy->id());
    $this->assertNotNull($loaded);
    $this->assertFalse($loaded->isOpen());

    $loaded->open();
    $repository->save($loaded);
    $this->assertTrue($repository->get($vacancy->id())?->isOpen());

    // The FULLTEXT index only sees committed rows (see VacancyFullTextSearchIntegrationTest); check the document.
    $this->assertSame(
      'integracao 100 php dev integracao 100 php dev integracao 100 php dev teste',
      $this->db->fetchValue('SELECT search_document FROM vacancies WHERE id = ?', [$vacancy->id()->value()])
    );

    $repository->remove($vacancy->id());
    $this->assertNull($repository->get($vacancy->id()));
  }

  public function testVacancyLifecycleRoundTrip(): void
  {
    $repository = new PdoVacancyRepository($this->db);
    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Rascunho integração'),
      VacancyDescription::fromString('Teste'),
      VacancyStatus::Draft,
      new \DateTimeImmutable('2026-01-01 10:00:00'),
      positions: VacancyPositions::fromInt(2)
    );
    $repository->save($vacancy);

    $draft = $repository->get($vacancy->id());
    $this->assertTrue($draft?->isDraft());
    $this->assertSame(2, $draft?->positions()->value());
    $ids = static fn (VacancyFilter $f): array => array_map(static fn (Vacancy $v): string => $v->id()->value(), $repository->matching($f, PageRequest::all()));
    $this->assertNotContains($vacancy->id()->value(), $ids(VacancyFilter::openOnly()));
    $this->assertContains($vacancy->id()->value(), $ids(new VacancyFilter(null, VacancyStatus::Draft)));

    $draft->open(new \DateTimeImmutable('2026-02-01 10:00:00'));
    $this->assertTrue($draft->closeIfFilled(2, new \DateTimeImmutable('2026-03-01 10:00:00')));
    $repository->save($draft);

    $closed = $repository->get($vacancy->id());
    $this->assertSame(VacancyStatus::Closed, $closed?->status());
    $this->assertSame(VacancyCloseReason::Filled, $closed?->closeReason());
    $this->assertSame('2026-03-01 10:00:00', $closed?->closedAt()?->format('Y-m-d H:i:s'));
    $this->assertSame('2026-02-01 10:00:00', $closed?->publishedAt()->format('Y-m-d H:i:s'));
  }

  public function testVacancyEmploymentTermsFiltersAndDeadlines(): void
  {
    $repository = new PdoVacancyRepository($this->db);
    $company = Company::register(CompanyId::generate(), CompanyName::fromString('ZZTerms Ltda'), 'Teste');
    (new PdoCompanyRepository($this->db))->save($company);
    $companyId = $company->id();
    $now = new \DateTimeImmutable('2026-05-10 12:00:00');
    $publish = static fn (string $title, WorkMode $mode, EmploymentTerms $terms, ?string $deadline): Vacancy => Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString($title),
      VacancyDescription::fromString('Teste'),
      VacancyStatus::Open,
      $now,
      $companyId,
      WorkArea::Backend,
      Seniority::Pleno,
      $mode,
      [],
      null,
      $terms,
      $deadline !== null ? VacancyDeadline::fromString($deadline) : null
    );

    $curitiba = $publish(
      'ZZTerms Curitiba',
      WorkMode::Hibrido,
      EmploymentTerms::of(ContractType::Pj, SalaryRange::of(9000, 12000), VacancyLocation::of('Curitiba', BrazilianState::PR), 'PLR, Plano de saúde'),
      '2026-05-20'
    );
    $negotiable = $publish('ZZTerms A combinar', WorkMode::Remoto, EmploymentTerms::standard(), null);
    $repository->save($curitiba);
    $repository->save($negotiable);

    $loaded = $repository->get($curitiba->id());
    $this->assertSame(ContractType::Pj, $loaded?->terms()->contractType());
    $this->assertSame('R$ 9.000 – R$ 12.000', $loaded?->terms()->salary()->label());
    $this->assertSame('Curitiba/PR', $loaded?->terms()->location()?->label());
    $this->assertSame(['Plano de saúde', 'PLR'], $loaded?->terms()->benefitLabels());
    $this->assertSame('2026-05-20', $loaded?->closesAt()?->value());
    $this->assertTrue($repository->get($negotiable->id())?->terms()->salary()->isNegotiable());

    $count = static fn (?int $minSalary = null, ?BrazilianState $state = null, ?ContractType $contract = null, ?\DateTimeImmutable $at = null): int => $repository->countMatching(
      new VacancyFilter(null, VacancyStatus::Open, $companyId, null, null, $minSalary, $state, $contract, $at ?? $now)
    );
    $this->assertSame(2, $count());
    $this->assertSame(1, $count(minSalary: 12000));
    $this->assertSame(0, $count(minSalary: 12001));
    $this->assertSame(1, $count(state: BrazilianState::PR));
    $this->assertSame(1, $count(contract: ContractType::Clt));
    $this->assertSame(2, $count(at: new \DateTimeImmutable('2026-05-20 23:00:00 America/Sao_Paulo')));
    $this->assertSame(1, $count(at: new \DateTimeImmutable('2026-05-21 00:30:00 America/Sao_Paulo')));

    $expiredIds = array_map(
      static fn (Vacancy $v): string => $v->id()->value(),
      $repository->openPastDeadline(new \DateTimeImmutable('2026-05-21 12:00:00'))
    );
    $this->assertContains($curitiba->id()->value(), $expiredIds);
    $this->assertNotContains($negotiable->id()->value(), $expiredIds);

    $loaded->revise($loaded->title(), $loaded->description(), workMode: WorkMode::Remoto, terms: EmploymentTerms::standard());
    $repository->save($loaded);
    $revised = $repository->get($curitiba->id());
    $this->assertNull($revised?->terms()->location());
    $this->assertSame([], $revised?->terms()->benefitLabels());
  }

  public function testRoleWithGrantsAndUsers(): void
  {
    $roles = new PdoRoleRepository($this->db);
    $permissions = new PdoPermissionRepository($this->db);
    $users = new PdoUserRepository($this->db);

    $permission = Permission::define(PermissionId::generate(), '', 'Teste', 'integration', 'run');
    $permissions->save($permission);

    $role = Role::create(RoleId::generate(), RoleName::fromString('integration-role'));
    $role->grant($permission->id());
    $roles->save($role);

    $this->assertTrue($roles->get($role->id())?->hasPermission($permission->id()));
    $this->assertSame(['integration.run'], $permissions->namesGrantedToRole($role->id()));

    $user = User::register(
      UserId::generate(),
      PersonName::fromString('Integration User'),
      EmailAddress::fromString('integration.user@example.com'),
      PlainPassword::fromString('123456'),
      $role->id()
    );
    $users->save($user);

    $this->assertTrue($users->findByEmail($user->email())?->verifyPassword('123456'));
    $this->assertSame(1, $users->countMatching(new UserFilter(SearchTerm::tryFrom('integration.user'), $role->id())));
    $this->assertSame(1, $users->countPerRole()[$role->id()->value()] ?? 0);

    $stored = $roles->get($role->id());
    $stored?->revoke($permission->id());
    $roles->save($stored);
    $this->assertSame([], $permissions->namesGrantedToRole($role->id()));
  }

  public function testJobApplication(): void
  {
    $vacancies = new PdoVacancyRepository($this->db);
    $users = new PdoUserRepository($this->db);
    $applications = new PdoJobApplicationRepository($this->db);

    $vacancy = Vacancy::publish(VacancyId::generate(), VacancyTitle::fromString('Vaga'), VacancyDescription::fromString('D'));
    $vacancies->save($vacancy);
    $user = User::register(
      UserId::generate(),
      PersonName::fromString('Candidato'),
      EmailAddress::fromString('candidate.integration@example.com'),
      PlainPassword::fromString('123456')
    );
    $users->save($user);

    $applications->save($vacancy->receiveApplicationFrom($user->id()));

    $this->assertTrue($applications->exists($user->id(), $vacancy->id()));
    $this->assertSame([$vacancy->id()->value()], $applications->vacancyIdsAppliedBy($user->id()));
    $this->assertCount(1, $applications->forVacancy($vacancy->id()));
    $this->assertSame(1, $applications->countPerVacancy()[$vacancy->id()->value()] ?? 0);
  }

  public function testJobApplicationPipelineHistoryAndNotesRoundTrip(): void
  {
    $vacancies = new PdoVacancyRepository($this->db);
    $users = new PdoUserRepository($this->db);
    $applications = new PdoJobApplicationRepository($this->db);

    $vacancy = Vacancy::publish(VacancyId::generate(), VacancyTitle::fromString('Vaga pipeline'), VacancyDescription::fromString('D'));
    $vacancies->save($vacancy);
    $candidate = User::register(
      UserId::generate(),
      PersonName::fromString('Candidata Pipeline'),
      EmailAddress::fromString('pipeline.candidate@example.com'),
      PlainPassword::fromString('123456')
    );
    $recruiter = User::register(
      UserId::generate(),
      PersonName::fromString('Recrutador Pipeline'),
      EmailAddress::fromString('pipeline.recruiter@example.com'),
      PlainPassword::fromString('123456')
    );
    $users->save($candidate);
    $users->save($recruiter);

    $application = $vacancy->receiveApplicationFrom($candidate->id(), new \DateTimeImmutable('2026-01-01 09:00:00'));
    $applications->save($application);
    $application->advance($recruiter->id(), new \DateTimeImmutable('2026-01-02 09:00:00'));
    $application->reject($recruiter->id(), 'Sem experiência em Go', new \DateTimeImmutable('2026-01-03 09:00:00'));
    $applications->save($application);

    $applications->addNote(ApplicationNote::write($application->id(), $recruiter->id(), 'Primeira nota'));
    $applications->addNote(ApplicationNote::write($application->id(), $recruiter->id(), 'Segunda nota'));

    $loaded = $applications->get($application->id());
    $this->assertNotNull($loaded);
    $this->assertSame(ApplicationStatus::Rejected, $loaded->status());
    $this->assertSame('Sem experiência em Go', $loaded->candidateFeedback());
    $this->assertSame('2026-01-03 09:00:00', $loaded->statusChangedAt()->format('Y-m-d H:i:s'));
    $this->assertSame(
      [ApplicationStatus::Received, ApplicationStatus::InReview, ApplicationStatus::Rejected],
      array_map(static fn ($e) => $e->to, $loaded->history())
    );
    $this->assertTrue($loaded->history()[2]->actorId?->equals($recruiter->id()));
    $this->assertSame('Sem experiência em Go', $loaded->history()[2]->reason);

    $notes = $applications->notesFor([$application->id()])[$application->id()->value()] ?? [];
    $this->assertSame(['Primeira nota', 'Segunda nota'], array_map(static fn (ApplicationNote $n): string => $n->body, $notes));

    $this->assertSame($application->id()->value(), $applications->findFor($candidate->id(), $vacancy->id())?->id()->value());
    $this->assertCount(1, $applications->forCandidate($candidate->id()));

    $loaded->reopen($recruiter->id());
    $loaded->withdraw($candidate->id());
    $applications->save($loaded);
    $this->assertSame([], $applications->vacancyIdsAppliedBy($candidate->id()));
    $this->assertArrayNotHasKey($vacancy->id()->value(), $applications->countPerVacancy());
    $this->assertCount(5, $applications->get($application->id())?->history() ?? []);
  }

  public function testCompanyCandidateProfileAndVacancyTopicsRoundTrip(): void
  {
    $companies = new PdoCompanyRepository($this->db);
    $users = new PdoUserRepository($this->db);
    $profiles = new PdoCandidateProfileRepository($this->db);
    $vacancies = new PdoVacancyRepository($this->db);

    $company = Company::register(
      CompanyId::generate(),
      CompanyName::fromString('Acme Tech Integration'),
      'Fintech',
      'https://acme.example.com',
      'Pagamentos instantâneos'
    );
    $companies->save($company);
    $this->assertSame('Fintech', $companies->get($company->id())?->sector());

    $user = User::register(
      UserId::generate(),
      PersonName::fromString('Candidata Perfil'),
      EmailAddress::fromString('perfil.integration@example.com'),
      PlainPassword::fromString('123456'),
      null,
      $company->id()
    );
    $users->save($user);
    $this->assertTrue($users->get($user->id())?->companyId()?->equals($company->id()));

    $profile = CandidateProfile::define(
      $user->id(),
      'Engenheira Backend',
      'Foco em PHP e bancos relacionais',
      WorkArea::Backend,
      Seniority::Senior,
      WorkMode::Remoto,
      'https://linkedin.com/in/candidata',
      'https://github.com/candidata',
      ['PHP', 'MySQL', 'Docker']
    );
    $profiles->save($profile);

    $loadedProfile = $profiles->getByUserId($user->id());
    $this->assertNotNull($loadedProfile);
    $this->assertSame('Engenheira Backend', $loadedProfile->headline());
    $this->assertSame(['Docker', 'MySQL', 'PHP'], $loadedProfile->topicLabels());

    $vacancy = Vacancy::publish(
      VacancyId::generate(),
      VacancyTitle::fromString('Arquiteta PHP'),
      VacancyDescription::fromString('Liderança técnica'),
      VacancyStatus::Open,
      null,
      $company->id(),
      WorkArea::Backend,
      Seniority::Senior,
      WorkMode::Remoto,
      ['PHP', 'DDD']
    );
    $vacancies->save($vacancy);

    $loadedVacancy = $vacancies->get($vacancy->id());
    $this->assertNotNull($loadedVacancy);
    $this->assertTrue($loadedVacancy->belongsToCompany($company->id()));
    $this->assertSame(['DDD', 'PHP'], $loadedVacancy->topicLabels());
    $this->assertSame(1, $vacancies->countMatching(new VacancyFilter(null, VacancyStatus::Open, $company->id(), WorkArea::Backend)));
  }
}

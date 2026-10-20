<?php

namespace Tests\Integration\Persistence;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
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
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyDescription;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
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
      VacancyStatus::Inactive
    );
    $repository->save($vacancy);

    $loaded = $repository->get($vacancy->id());
    $this->assertNotNull($loaded);
    $this->assertFalse($loaded->isOpen());

    $loaded->open();
    $repository->save($loaded);
    $this->assertTrue($repository->get($vacancy->id())?->isOpen());

    $filter = new VacancyFilter(SearchTerm::tryFrom('100% PHP_dev'), VacancyStatus::Active);
    $this->assertSame(1, $repository->countMatching($filter));
    $this->assertCount(1, $repository->matching($filter, new PageRequest(1, 5)));
    $this->assertSame(0, $repository->countMatching(new VacancyFilter(SearchTerm::tryFrom('1000% PHP'))));

    $repository->remove($vacancy->id());
    $this->assertNull($repository->get($vacancy->id()));
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
      VacancyStatus::Active,
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
    $this->assertSame(1, $vacancies->countMatching(new VacancyFilter(null, VacancyStatus::Active, $company->id(), WorkArea::Backend)));
  }
}

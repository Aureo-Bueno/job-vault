<?php

namespace Tests\Unit\Application;

use App\Application\Auth\AuthService;
use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Companies\CreateCompanyCommand;
use App\Application\Commands\Companies\DeleteCompanyCommand;
use App\Application\Commands\Companies\UpdateCompanyCommand;
use App\Application\Commands\Profile\SaveMyCandidateProfileCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Applications\ListVacancyApplicantsHandler;
use App\Application\Features\Companies\CreateCompanyHandler;
use App\Application\Features\Companies\DeleteCompanyHandler;
use App\Application\Features\Companies\GetMyCompanyHandler;
use App\Application\Features\Companies\ListCompaniesHandler;
use App\Application\Features\Companies\UpdateCompanyHandler;
use App\Application\Features\Profile\GetMyCandidateProfileHandler;
use App\Application\Features\Profile\SaveMyCandidateProfileHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\DeleteVacancyHandler;
use App\Application\Features\Vacancies\ListOpenVacanciesHandler;
use App\Application\Features\Vacancies\SearchVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Queries\Applications\ListVacancyApplicantsQuery;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Application\Queries\Profile\GetMyCandidateProfileQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use App\Application\Users\UserRegistrar;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAuthorization;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryCandidateProfileRepository;
use Tests\Support\InMemoryCompanyRepository;
use Tests\Support\InMemoryJobApplicationRepository;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemorySession;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\InMemoryVacancyRepository;
use Tests\Support\NullLogger;

final class CompanyProfileAndMatchingHandlersTest extends TestCase
{
  private InMemoryCompanyRepository $companies;
  private InMemoryUserRepository $users;
  private InMemoryRoleRepository $roles;
  private InMemoryCandidateProfileRepository $profiles;
  private InMemoryVacancyRepository $vacancies;
  private InMemoryJobApplicationRepository $applications;
  private UserRegistrar $registrar;

  protected function setUp(): void
  {
    $this->companies = new InMemoryCompanyRepository();
    $this->users = new InMemoryUserRepository();
    $this->roles = new InMemoryRoleRepository();
    $this->profiles = new InMemoryCandidateProfileRepository();
    $this->vacancies = new InMemoryVacancyRepository();
    $this->applications = new InMemoryJobApplicationRepository();
    $this->registrar = new UserRegistrar($this->users, $this->roles, $this->companies);

    $this->roles->save(Role::create(RoleId::generate(), RoleName::recruiter()));
    $this->roles->save(Role::create(RoleId::generate(), RoleName::defaultUser()));
  }

  public function testCompanyCrudAndSelfRegistration(): void
  {
    $auth = new AuthService($this->users, $this->registrar, new InMemorySession(), new NullLogger());
    $recruiterAuth = $auth->registerCompany(
      'Carla Recrutadora',
      'carla@technova.com',
      '123456',
      'TechNova Ltda',
      'SaaS',
      'https://technova.com',
      'Empresa de SaaS'
    );

    $storedUser = $this->users->get(UserId::fromString($recruiterAuth->id));
    $this->assertNotNull($storedUser?->companyId());

    $currentUser = FakeCurrentUser::as($recruiterAuth->id);
    $myCompany = (new GetMyCompanyHandler($this->companies, $this->users, $currentUser))->handle(new GetMyCompanyQuery());
    $this->assertNotNull($myCompany);
    $this->assertSame('TechNova Ltda', $myCompany->name);
    $this->assertSame(1, $myCompany->userCount);

    $otherCompany = (new CreateCompanyHandler($this->companies))->handle(new CreateCompanyCommand('Outra Corp', 'Varejo'));
    $this->assertCount(2, (new ListCompaniesHandler($this->companies, $this->users))->handle(new ListCompaniesQuery()));

    $nonAdminAuth = new FakeAuthorization(['vacancy.manage'], false, 'gestor');
    $updateHandler = new UpdateCompanyHandler($this->companies, $this->users, $currentUser, $nonAdminAuth);
    $updateHandler->handle(new UpdateCompanyCommand($myCompany->id, 'TechNova Global', 'Fintech'));
    $this->assertSame('TechNova Global', $this->companies->get($storedUser->companyId())?->name()->value());

    $this->expectException(ForbiddenException::class);
    $updateHandler->handle(new UpdateCompanyCommand($otherCompany->id, 'Hack Corp', 'Fintech'));
  }

  public function testDuplicateCompanyNameRejected(): void
  {
    (new CreateCompanyHandler($this->companies))->handle(new CreateCompanyCommand('Unica Corp'));

    $this->expectException(AlreadyExistsException::class);
    (new CreateCompanyHandler($this->companies))->handle(new CreateCompanyCommand('unica corp'));
  }

  public function testDeleteCompany(): void
  {
    $created = (new CreateCompanyHandler($this->companies))->handle(new CreateCompanyCommand('Temp Corp'));
    (new DeleteCompanyHandler($this->companies))->handle(new DeleteCompanyCommand($created->id));
    $this->assertCount(0, (new ListCompaniesHandler($this->companies))->handle(new ListCompaniesQuery()));
  }

  public function testRecruiterCanOnlyManageOwnCompanyVacancies(): void
  {
    $recruiterA = $this->registrar->registerCompanyAccount('Rec A', 'a@compA.com', '123456', 'Empresa A');
    $recruiterB = $this->registrar->registerCompanyAccount('Rec B', 'b@compB.com', '123456', 'Empresa B');

    $scopeA = new VacancyAccessScope(
      FakeCurrentUser::as($recruiterA->id()->value()),
      $this->users,
      new FakeAuthorization(['vacancy.manage', 'vacancy.create', 'vacancy.edit', 'vacancy.delete'], false, 'gestor'),
      $this->companies
    );
    $scopeB = new VacancyAccessScope(
      FakeCurrentUser::as($recruiterB->id()->value()),
      $this->users,
      new FakeAuthorization(['vacancy.manage', 'vacancy.create', 'vacancy.edit', 'vacancy.delete'], false, 'gestor'),
      $this->companies
    );

    $vacancyA = (new CreateVacancyHandler($this->vacancies, $scopeA, $this->companies))->handle(
      new CreateVacancyCommand('Vaga A', 'Desc A', true, 'backend', 'pleno', 'remoto', ['PHP', 'MySQL'])
    );
    $vacancyB = (new CreateVacancyHandler($this->vacancies, $scopeB, $this->companies))->handle(
      new CreateVacancyCommand('Vaga B', 'Desc B', true, 'frontend', 'senior', 'hibrido', ['React'])
    );

    $this->assertSame('Empresa A', $vacancyA->companyName);
    $this->assertSame(1, (new SearchVacanciesHandler($this->vacancies, $scopeA, $this->companies))->handle(new SearchVacanciesQuery())->total);

    $this->expectException(ForbiddenException::class);
    (new DeleteVacancyHandler($this->vacancies, $scopeA))->handle(new DeleteVacancyCommand($vacancyB->id));
  }

  public function testCandidateProfileOpenVacanciesMatchAndApplicantsSorting(): void
  {
    $recruiter = $this->registrar->registerCompanyAccount('Rec', 'rec@comp.com', '123456', 'TechNova');
    $candidateStrong = $this->registrar->register('Alice', 'alice@example.com', '123456');
    $candidateWeak = $this->registrar->register('Bruno', 'bruno@example.com', '123456');

    $aliceCurrent = FakeCurrentUser::as($candidateStrong->id()->value());
    $brunoCurrent = FakeCurrentUser::as($candidateWeak->id()->value());

    (new SaveMyCandidateProfileHandler($this->profiles, $aliceCurrent))->handle(
      new SaveMyCandidateProfileCommand(
        'Dev Backend PHP Pleno',
        'Especialista em PHP e MySQL',
        'backend',
        'pleno',
        'remoto',
        'https://linkedin.com/in/alice',
        'https://github.com/alice',
        ['PHP', 'MySQL', 'DDD', 'Docker']
      )
    );
    (new SaveMyCandidateProfileHandler($this->profiles, $brunoCurrent))->handle(
      new SaveMyCandidateProfileCommand(
        'Dev Mobile Jr',
        'Iniciante em mobile',
        'mobile',
        'junior',
        'presencial',
        '',
        '',
        ['Flutter']
      )
    );

    $aliceProfileDto = (new GetMyCandidateProfileHandler($this->profiles, $aliceCurrent))->handle(new GetMyCandidateProfileQuery());
    $this->assertTrue($aliceProfileDto->isComplete);

    $scopeRecruiter = new VacancyAccessScope(
      FakeCurrentUser::as($recruiter->id()->value()),
      $this->users,
      new FakeAuthorization(['vacancy.manage'], false, 'gestor'),
      $this->companies
    );
    $createVacancy = new CreateVacancyHandler($this->vacancies, $scopeRecruiter, $this->companies);

    $lowVacancy = $createVacancy->handle(
      new CreateVacancyCommand('Vaga Mobile Flutter', 'App mobile', true, 'mobile', 'junior', 'presencial', ['Flutter', 'Dart'])
    );
    $highVacancy = $createVacancy->handle(
      new CreateVacancyCommand('Vaga Backend PHP', 'APIs e DDD', true, 'backend', 'pleno', 'remoto', ['PHP', 'MySQL', 'DDD'])
    );

    $openForAlice = (new ListOpenVacanciesHandler($this->vacancies, $this->companies, $this->profiles, $aliceCurrent))
      ->handle(new ListOpenVacanciesQuery(sort: 'match'));

    $this->assertCount(2, $openForAlice);
    $this->assertSame($highVacancy->id, $openForAlice[0]->id);
    $this->assertSame(100, $openForAlice[0]->matchScore);
    $this->assertLessThan(30, $openForAlice[1]->matchScore);

    (new ApplyToVacancyHandler($this->vacancies, $this->applications, $brunoCurrent))
      ->handle(new ApplyToVacancyCommand($highVacancy->id));
    (new ApplyToVacancyHandler($this->vacancies, $this->applications, $aliceCurrent))
      ->handle(new ApplyToVacancyCommand($highVacancy->id));

    $applicants = (new ListVacancyApplicantsHandler(
      $this->vacancies,
      $this->applications,
      $this->users,
      $this->profiles,
      $scopeRecruiter
    ))->handle(new ListVacancyApplicantsQuery($highVacancy->id));

    $this->assertCount(2, $applicants);
    $this->assertSame('Alice', $applicants[0]->name);
    $this->assertSame(100, $applicants[0]->matchScore);
    $this->assertSame('Bruno', $applicants[1]->name);
    $this->assertLessThan($applicants[0]->matchScore, $applicants[1]->matchScore);
  }
}

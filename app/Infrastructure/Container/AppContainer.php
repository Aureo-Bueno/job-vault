<?php

namespace App\Infrastructure\Container;

use App\Application\Abstractions\CommandBusInterface;
use App\Application\Abstractions\QueryBusInterface;
use App\Application\Abstractions\SimpleCommandBus;
use App\Application\Abstractions\SimpleQueryBus;
use App\Application\Auth\AuthService;
use App\Application\Behaviors\AuthorizationBehavior;
use App\Application\Behaviors\LoggingBehavior;
use App\Application\Behaviors\TransactionBehavior;
use App\Application\Features\Applications\AddApplicationNoteHandler;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Applications\GetVacancyPipelineHandler;
use App\Application\Features\Applications\ListMyApplicationsHandler;
use App\Application\Features\Applications\ListMyAppliedVacancyIdsHandler;
use App\Application\Features\Applications\ListVacancyApplicantsHandler;
use App\Application\Features\Applications\MoveApplicationHandler;
use App\Application\Features\Applications\WithdrawApplicationHandler;
use App\Application\Features\Companies\CreateCompanyHandler;
use App\Application\Features\Companies\DeleteCompanyHandler;
use App\Application\Features\Companies\GetMyCompanyHandler;
use App\Application\Features\Companies\ListCompaniesHandler;
use App\Application\Features\Companies\UpdateCompanyHandler;
use App\Application\Features\Permissions\CreatePermissionHandler;
use App\Application\Features\Permissions\DeletePermissionHandler;
use App\Application\Features\Permissions\ListPermissionsHandler;
use App\Application\Features\Permissions\UpdatePermissionHandler;
use App\Application\Features\Profile\GetMyCandidateProfileHandler;
use App\Application\Features\Profile\SaveMyCandidateProfileHandler;
use App\Application\Features\RolePermissions\AssignPermissionToRoleHandler;
use App\Application\Features\RolePermissions\RemovePermissionFromRoleHandler;
use App\Application\Features\Roles\CreateRoleHandler;
use App\Application\Features\Roles\DeleteRoleHandler;
use App\Application\Features\Roles\ListRolesHandler;
use App\Application\Features\Roles\UpdateRoleHandler;
use App\Application\Features\Users\CreateUserHandler;
use App\Application\Features\Users\DeleteUserHandler;
use App\Application\Features\Users\GetUserByIdHandler;
use App\Application\Features\Users\SearchUsersHandler;
use App\Application\Features\Users\UpdateUserHandler;
use App\Application\Features\Vacancies\CloseExpiredVacanciesHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\DeleteVacancyHandler;
use App\Application\Features\Vacancies\GetVacancyByIdHandler;
use App\Application\Features\Vacancies\ListOpenVacanciesHandler;
use App\Application\Features\Vacancies\SearchVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Ports\LoggerInterface;
use App\Application\Ports\SessionInterface;
use App\Application\Security\Authorization;
use App\Application\Security\MessageAuthorizer;
use App\Application\Security\RoleBasedAuthorization;
use App\Application\Users\UserRegistrar;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\Matching\MatchCalculator;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\RoleRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Infrastructure\Http\NativeSession;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\PdoCandidateProfileRepository;
use App\Infrastructure\Persistence\PdoCompanyRepository;
use App\Infrastructure\Persistence\PdoJobApplicationRepository;
use App\Infrastructure\Persistence\PdoPermissionRepository;
use App\Infrastructure\Persistence\PdoRoleRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoVacancyRepository;

/**
 * Composition root. Only the presentation edge (front controller/pages) should use it;
 * every other class receives its dependencies through the constructor.
 *
 * Services are created lazily and shared for the duration of the request.
 * Tests can replace any service id via setOverride().
 */
final class AppContainer
{
  /** @var array<string,object> */
  private static array $instances = [];

  /** @var array<string,object> */
  private static array $overrides = [];

  public static function setOverride(string $id, object $service): void
  {
    self::$overrides[$id] = $service;
  }

  public static function reset(): void
  {
    self::$instances = [];
    self::$overrides = [];
  }

  public static function database(): Database
  {
    return self::shared('database', static fn (): Database => Database::fromEnvironment());
  }

  public static function session(): SessionInterface
  {
    return self::shared('session', static fn (): SessionInterface => new NativeSession());
  }

  public static function logger(string $channel = 'app'): LoggerInterface
  {
    return self::shared('logger.' . $channel, static fn (): LoggerInterface => new FileLogger($channel));
  }

  public static function authService(): AuthService
  {
    return self::shared('authService', static fn (): AuthService => new AuthService(
      self::userRepository(),
      self::userRegistrar(),
      self::session(),
      self::logger('login')
    ));
  }

  public static function authorization(): Authorization
  {
    return self::shared('authorization', static fn (): Authorization => new RoleBasedAuthorization(
      self::userRepository(),
      self::roleRepository(),
      self::permissionRepository()
    ));
  }

  public static function commandBus(): CommandBusInterface
  {
    return self::shared('commandBus', static function (): CommandBusInterface {
      $users = self::userRepository();
      $roles = self::roleRepository();
      $permissions = self::permissionRepository();
      $vacancies = self::vacancyRepository();
      $companies = self::companyRepository();
      $profiles = self::candidateProfileRepository();
      $currentUser = self::authService();
      $authorization = self::authorization();
      $registrar = self::userRegistrar();
      $vacancyScope = self::vacancyAccessScope();
      $applications = self::jobApplicationRepository();
      $database = self::database();

      return new SimpleCommandBus(
        [
          new CreateUserHandler($registrar),
          new UpdateUserHandler($users, $registrar),
          new DeleteUserHandler($users, $currentUser),
          new CreateRoleHandler($roles),
          new UpdateRoleHandler($roles),
          new DeleteRoleHandler($roles, $users),
          new CreatePermissionHandler($permissions),
          new UpdatePermissionHandler($permissions),
          new DeletePermissionHandler($permissions),
          new AssignPermissionToRoleHandler($roles, $permissions),
          new RemovePermissionFromRoleHandler($roles),
          new CreateCompanyHandler($companies),
          new UpdateCompanyHandler($companies, $users, $currentUser, $authorization),
          new DeleteCompanyHandler($companies),
          new SaveMyCandidateProfileHandler($profiles, $currentUser),
          new CreateVacancyHandler($vacancies, $vacancyScope, $companies),
          new UpdateVacancyHandler($vacancies, $vacancyScope),
          new DeleteVacancyHandler($vacancies, $vacancyScope),
          new ApplyToVacancyHandler($vacancies, $applications, $currentUser),
          new WithdrawApplicationHandler($applications, $currentUser),
          new MoveApplicationHandler($applications, $vacancies, $vacancyScope, $currentUser),
          new AddApplicationNoteHandler($applications, $vacancies, $vacancyScope, $currentUser),
        ],
        [
          new AuthorizationBehavior([self::messageAuthorizer(), 'authorize']),
          new TransactionBehavior(
            [$database, 'beginTransaction'],
            [$database, 'commit'],
            [$database, 'rollBack']
          ),
          new LoggingBehavior(self::logger('pipeline')),
        ]
      );
    });
  }

  /**
   * Bus for scheduled/system jobs (bin/console): no logged-in user, so no authorization step.
   * Never expose it to HTTP requests.
   */
  public static function systemCommandBus(): CommandBusInterface
  {
    return self::shared('systemCommandBus', static function (): CommandBusInterface {
      $database = self::database();

      return new SimpleCommandBus(
        [
          new CloseExpiredVacanciesHandler(self::vacancyRepository()),
        ],
        [
          new TransactionBehavior(
            [$database, 'beginTransaction'],
            [$database, 'commit'],
            [$database, 'rollBack']
          ),
          new LoggingBehavior(self::logger('scheduler')),
        ]
      );
    });
  }

  public static function queryBus(): QueryBusInterface
  {
    return self::shared('queryBus', static function (): QueryBusInterface {
      $users = self::userRepository();
      $vacancies = self::vacancyRepository();
      $companies = self::companyRepository();
      $profiles = self::candidateProfileRepository();
      $applications = self::jobApplicationRepository();
      $currentUser = self::authService();
      $vacancyScope = self::vacancyAccessScope();
      $calculator = new MatchCalculator();
      $applicants = new ListVacancyApplicantsHandler($vacancies, $applications, $users, $profiles, $vacancyScope, $calculator);

      return new SimpleQueryBus(
        [
          new SearchUsersHandler($users),
          new GetUserByIdHandler($users),
          new ListRolesHandler(self::roleRepository(), $users),
          new ListPermissionsHandler(self::permissionRepository()),
          new ListCompaniesHandler($companies, $users),
          new GetMyCompanyHandler($companies, $users, $currentUser),
          new GetMyCandidateProfileHandler($profiles, $currentUser),
          new SearchVacanciesHandler($vacancies, $vacancyScope, $companies, $applications),
          new ListOpenVacanciesHandler($vacancies, $companies, $profiles, $currentUser, $calculator),
          new GetVacancyByIdHandler($vacancies, $vacancyScope, $companies, $applications),
          new ListMyAppliedVacancyIdsHandler($applications, $currentUser),
          $applicants,
          new GetVacancyPipelineHandler($applicants),
          new ListMyApplicationsHandler($applications, $vacancies, $companies, $currentUser),
        ],
        [
          new AuthorizationBehavior([self::messageAuthorizer(), 'authorize']),
          new LoggingBehavior(self::logger('pipeline')),
        ]
      );
    });
  }

  private static function vacancyAccessScope(): VacancyAccessScope
  {
    return self::shared('vacancyAccessScope', static fn (): VacancyAccessScope => new VacancyAccessScope(
      self::authService(),
      self::userRepository(),
      self::authorization(),
      self::companyRepository()
    ));
  }

  private static function messageAuthorizer(): MessageAuthorizer
  {
    return self::shared('messageAuthorizer', static fn (): MessageAuthorizer => new MessageAuthorizer(
      self::authService(),
      self::authorization()
    ));
  }

  private static function userRegistrar(): UserRegistrar
  {
    return self::shared('userRegistrar', static fn (): UserRegistrar => new UserRegistrar(
      self::userRepository(),
      self::roleRepository(),
      self::companyRepository()
    ));
  }

  private static function userRepository(): UserRepositoryInterface
  {
    return self::shared('userRepository', static fn () => new PdoUserRepository(self::database()));
  }

  private static function roleRepository(): RoleRepositoryInterface
  {
    return self::shared('roleRepository', static fn () => new PdoRoleRepository(self::database()));
  }

  private static function permissionRepository(): PermissionRepositoryInterface
  {
    return self::shared('permissionRepository', static fn () => new PdoPermissionRepository(self::database()));
  }

  private static function vacancyRepository(): VacancyRepositoryInterface
  {
    return self::shared('vacancyRepository', static fn () => new PdoVacancyRepository(self::database()));
  }

  private static function companyRepository(): CompanyRepositoryInterface
  {
    return self::shared('companyRepository', static fn () => new PdoCompanyRepository(self::database()));
  }

  private static function candidateProfileRepository(): CandidateProfileRepositoryInterface
  {
    return self::shared('candidateProfileRepository', static fn () => new PdoCandidateProfileRepository(self::database()));
  }

  private static function jobApplicationRepository(): JobApplicationRepositoryInterface
  {
    return self::shared('jobApplicationRepository', static fn () => new PdoJobApplicationRepository(self::database()));
  }

  /**
   * @template T of object
   * @param callable():T $factory
   * @return T
   */
  private static function shared(string $id, callable $factory): object
  {
    return self::$overrides[$id] ?? (self::$instances[$id] ??= $factory());
  }
}

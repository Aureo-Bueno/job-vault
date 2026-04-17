<?php

namespace App\Infrastructure\Container;

use App\Application\Abstractions\CommandBusInterface;
use App\Application\Abstractions\QueryBusInterface;
use App\Application\Abstractions\SimpleCommandBus;
use App\Application\Abstractions\SimpleQueryBus;
use App\Application\Behaviors\AuthorizationBehavior;
use App\Application\Behaviors\BehaviorInterface;
use App\Application\Behaviors\LoggingBehavior;
use App\Application\Behaviors\TransactionBehavior;
use App\Application\Behaviors\ValidationBehavior;
use App\Application\Features\Applications\ApplyToVacancyHandler;
use App\Application\Features\Applications\ListAppliedVacancyIdsByUserHandler;
use App\Application\Features\Permissions\CreatePermissionHandler;
use App\Application\Features\Permissions\DeletePermissionHandler;
use App\Application\Features\Permissions\ListPermissionsHandler;
use App\Application\Features\Permissions\UpdatePermissionHandler;
use App\Application\Features\RolePermissions\AssignPermissionToRoleHandler;
use App\Application\Features\RolePermissions\ListPermissionsByRoleHandler;
use App\Application\Features\RolePermissions\RemovePermissionFromRoleHandler;
use App\Application\Features\Roles\CreateRoleHandler;
use App\Application\Features\Roles\DeleteRoleHandler;
use App\Application\Features\Roles\ListRolesHandler;
use App\Application\Features\Roles\UpdateRoleHandler;
use App\Application\Features\Users\CountUsersHandler;
use App\Application\Features\Users\CreateUserHandler;
use App\Application\Features\Users\DeleteUserHandler;
use App\Application\Features\Users\GetUserByEmailHandler;
use App\Application\Features\Users\GetUserByIdHandler;
use App\Application\Features\Users\ListUsersHandler;
use App\Application\Features\Users\UpdateUserHandler;
use App\Application\Features\Vacancies\CountVacanciesHandler;
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\DeleteVacancyHandler;
use App\Application\Features\Vacancies\GetVacancyByIdHandler;
use App\Application\Features\Vacancies\ListVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Service\AccessControlService;
use App\Application\Service\ApplicationService;
use App\Application\Service\AuthService;
use App\Application\Service\RoleService;
use App\Application\Service\UserService;
use App\Application\Service\VacancyService;
use App\Db\Database;
use App\Domain\Repository\ApplicationRepositoryInterface;
use App\Domain\Repository\PermissionRepositoryInterface;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\VacancyRepositoryInterface;
use App\Infrastructure\Persistence\PdoApplicationRepository;
use App\Infrastructure\Persistence\PdoPermissionRepository;
use App\Infrastructure\Persistence\PdoRolePermissionRepository;
use App\Infrastructure\Persistence\PdoRoleRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\PdoVacancyRepository;
use App\Util\Logger;

class AppContainer
{
  private static ?VacancyRepositoryInterface $vacancyRepository = null;
  private static ?UserRepositoryInterface $userRepository = null;
  private static ?RoleRepositoryInterface $roleRepository = null;
  private static ?PermissionRepositoryInterface $permissionRepository = null;
  private static ?RolePermissionRepositoryInterface $rolePermissionRepository = null;
  private static ?ApplicationRepositoryInterface $applicationRepository = null;

  private static ?VacancyService $vacancyService = null;
  private static ?AuthService $authService = null;
  private static ?RoleService $roleService = null;
  private static ?UserService $userService = null;
  private static ?ApplicationService $applicationService = null;
  private static ?AccessControlService $accessControlService = null;
  private static ?CommandBusInterface $commandBus = null;
  private static ?QueryBusInterface $queryBus = null;

  /** @var array<string,mixed> */
  private static array $overrides = [];

  public static function setOverride(string $key, mixed $service): void
  {
    self::$overrides[$key] = $service;
  }

  public static function clearOverrides(): void
  {
    self::$overrides = [];
  }

  public static function reset(): void
  {
    self::$vacancyRepository = null;
    self::$userRepository = null;
    self::$roleRepository = null;
    self::$permissionRepository = null;
    self::$rolePermissionRepository = null;
    self::$applicationRepository = null;
    self::$vacancyService = null;
    self::$authService = null;
    self::$roleService = null;
    self::$userService = null;
    self::$applicationService = null;
    self::$accessControlService = null;
    self::$commandBus = null;
    self::$queryBus = null;
    self::$overrides = [];
  }

  public static function vacancyRepository(): VacancyRepositoryInterface
  {
    if (!self::$vacancyRepository) {
      self::$vacancyRepository = new PdoVacancyRepository();
    }

    return self::$vacancyRepository;
  }

  public static function userRepository(): UserRepositoryInterface
  {
    if (!self::$userRepository) {
      self::$userRepository = new PdoUserRepository();
    }

    return self::$userRepository;
  }

  public static function roleRepository(): RoleRepositoryInterface
  {
    if (!self::$roleRepository) {
      self::$roleRepository = new PdoRoleRepository();
    }

    return self::$roleRepository;
  }

  public static function permissionRepository(): PermissionRepositoryInterface
  {
    if (!self::$permissionRepository) {
      self::$permissionRepository = new PdoPermissionRepository();
    }

    return self::$permissionRepository;
  }

  public static function rolePermissionRepository(): RolePermissionRepositoryInterface
  {
    if (!self::$rolePermissionRepository) {
      self::$rolePermissionRepository = new PdoRolePermissionRepository();
    }

    return self::$rolePermissionRepository;
  }

  public static function applicationRepository(): ApplicationRepositoryInterface
  {
    if (!self::$applicationRepository) {
      self::$applicationRepository = new PdoApplicationRepository();
    }

    return self::$applicationRepository;
  }

  public static function vacancyService(): VacancyService
  {
    if (!self::$vacancyService) {
      self::$vacancyService = new VacancyService(self::vacancyRepository());
    }

    return self::$vacancyService;
  }

  public static function authService(): AuthService
  {
    $override = self::$overrides['authService'] ?? null;
    if ($override instanceof AuthService) {
      return $override;
    }

    if (!self::$authService) {
      self::$authService = new AuthService(self::userRepository());
    }

    return self::$authService;
  }

  public static function roleService(): RoleService
  {
    $override = self::$overrides['roleService'] ?? null;
    if ($override instanceof RoleService) {
      return $override;
    }

    if (!self::$roleService) {
      self::$roleService = new RoleService(
        self::userRepository(),
        self::roleRepository(),
        self::rolePermissionRepository()
      );
    }

    return self::$roleService;
  }

  public static function userService(): UserService
  {
    if (!self::$userService) {
      self::$userService = new UserService(self::userRepository());
    }

    return self::$userService;
  }

  public static function applicationService(): ApplicationService
  {
    if (!self::$applicationService) {
      self::$applicationService = new ApplicationService(self::applicationRepository());
    }

    return self::$applicationService;
  }

  public static function accessControlService(): AccessControlService
  {
    if (!self::$accessControlService) {
      self::$accessControlService = new AccessControlService(
        self::roleRepository(),
        self::permissionRepository(),
        self::rolePermissionRepository(),
        self::userRepository()
      );
    }

    return self::$accessControlService;
  }

  public static function commandBus(): CommandBusInterface
  {
    $override = self::$overrides['commandBus'] ?? null;
    if ($override instanceof CommandBusInterface) {
      return $override;
    }

    if (!self::$commandBus) {
      self::$commandBus = new SimpleCommandBus(
        [
          new CreateUserHandler(self::userService()),
          new UpdateUserHandler(self::userService()),
          new DeleteUserHandler(self::userService()),
          new CreateRoleHandler(self::accessControlService()),
          new UpdateRoleHandler(self::accessControlService()),
          new DeleteRoleHandler(self::accessControlService()),
          new CreatePermissionHandler(self::accessControlService()),
          new UpdatePermissionHandler(self::accessControlService()),
          new DeletePermissionHandler(self::accessControlService()),
          new AssignPermissionToRoleHandler(self::accessControlService()),
          new RemovePermissionFromRoleHandler(self::accessControlService()),
          new CreateVacancyHandler(self::vacancyService()),
          new UpdateVacancyHandler(self::vacancyService()),
          new DeleteVacancyHandler(self::vacancyService()),
          new ApplyToVacancyHandler(self::applicationService()),
        ],
        self::commandBehaviors()
      );
    }

    return self::$commandBus;
  }

  public static function queryBus(): QueryBusInterface
  {
    $override = self::$overrides['queryBus'] ?? null;
    if ($override instanceof QueryBusInterface) {
      return $override;
    }

    if (!self::$queryBus) {
      self::$queryBus = new SimpleQueryBus(
        [
          new CountUsersHandler(self::userService()),
          new ListUsersHandler(self::userService()),
          new GetUserByIdHandler(self::userService()),
          new GetUserByEmailHandler(self::userService()),
          new ListRolesHandler(self::accessControlService()),
          new ListPermissionsHandler(self::accessControlService()),
          new ListPermissionsByRoleHandler(self::accessControlService()),
          new CountVacanciesHandler(self::vacancyService()),
          new ListVacanciesHandler(self::vacancyService()),
          new GetVacancyByIdHandler(self::vacancyService()),
          new ListAppliedVacancyIdsByUserHandler(self::applicationService()),
        ],
        self::queryBehaviors()
      );
    }

    return self::$queryBus;
  }

  /**
   * @return array<int,BehaviorInterface>
   */
  private static function commandBehaviors(): array
  {
    return [
      new ValidationBehavior(static function (object $message): void {
        MessagePipelineGuard::validate($message);
      }),
      new AuthorizationBehavior(static function (object $message): void {
        MessagePipelineGuard::authorize($message);
      }),
      new TransactionBehavior(
        static function (): void {
          $connection = Database::sharedConnection();
          if (!$connection->inTransaction()) {
            $connection->beginTransaction();
          }
        },
        static function (): void {
          $connection = Database::sharedConnection();
          if ($connection->inTransaction()) {
            $connection->commit();
          }
        },
        static function (): void {
          $connection = Database::sharedConnection();
          if ($connection->inTransaction()) {
            $connection->rollBack();
          }
        }
      ),
      new LoggingBehavior(static function (string $message, array $context = []): void {
        static $pipelineLogger = null;
        if (!$pipelineLogger instanceof Logger) {
          $pipelineLogger = new Logger('pipeline');
        }

        $pipelineLogger->info($message, $context);
      }),
    ];
  }

  /**
   * @return array<int,BehaviorInterface>
   */
  private static function queryBehaviors(): array
  {
    return [
      new ValidationBehavior(static function (object $message): void {
        MessagePipelineGuard::validate($message);
      }),
      new AuthorizationBehavior(static function (object $message): void {
        MessagePipelineGuard::authorize($message);
      }),
      new LoggingBehavior(static function (string $message, array $context = []): void {
        static $pipelineLogger = null;
        if (!$pipelineLogger instanceof Logger) {
          $pipelineLogger = new Logger('pipeline');
        }

        $pipelineLogger->info($message, $context);
      }),
    ];
  }
}

<?php

namespace App\Infrastructure\Container;

use App\Application\Abstractions\CommandBusInterface;
use App\Application\Abstractions\QueryBusInterface;
use App\Application\Abstractions\SimpleCommandBus;
use App\Application\Abstractions\SimpleQueryBus;
use App\Application\Behaviors\AuthorizationBehavior;
use App\Application\Behaviors\LoggingBehavior;
use App\Application\Behaviors\TransactionBehavior;
use App\Application\Behaviors\ValidationBehavior;
use App\Application\Commands\Applications\ApplyToVacancyCommand;
use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\Commands\Permissions\DeletePermissionCommand;
use App\Application\Commands\Permissions\UpdatePermissionCommand;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\Commands\Roles\DeleteRoleCommand;
use App\Application\Commands\Roles\UpdateRoleCommand;
use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Commands\Vacancies\UpdateVacancyCommand;
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
use App\Application\Features\Vacancies\CreateVacancyHandler;
use App\Application\Features\Vacancies\CountVacanciesHandler;
use App\Application\Features\Vacancies\DeleteVacancyHandler;
use App\Application\Features\Vacancies\GetVacancyByIdHandler;
use App\Application\Features\Vacancies\ListVacanciesHandler;
use App\Application\Features\Vacancies\UpdateVacancyHandler;
use App\Application\Queries\Applications\ListAppliedVacancyIdsByUserQuery;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\RolePermissions\ListPermissionsByRoleQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\CountUsersQuery;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Queries\Users\ListUsersQuery;
use App\Application\Queries\Vacancies\CountVacanciesQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Queries\Vacancies\ListVacanciesQuery;
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
use App\Util\IdValidator;
use App\Util\Logger;
use App\Util\RoleManager;
use InvalidArgumentException;
use RuntimeException;

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
  private static ?Logger $pipelineLogger = null;
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
    self::$pipelineLogger = null;
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
   * @return array<int,object>
   */
  private static function commandBehaviors(): array
  {
    return [
      new ValidationBehavior(function (object $message): void {
        self::validateMessage($message);
      }),
      new AuthorizationBehavior(function (object $message): void {
        self::authorizeMessage($message);
      }),
      new TransactionBehavior(
        function (): void {
          self::beginTransaction();
        },
        function (): void {
          self::commitTransaction();
        },
        function (): void {
          self::rollbackTransaction();
        }
      ),
      new LoggingBehavior(function (string $message, array $context = []): void {
        self::pipelineLogger()->info($message, $context);
      }),
    ];
  }

  /**
   * @return array<int,object>
   */
  private static function queryBehaviors(): array
  {
    return [
      new ValidationBehavior(function (object $message): void {
        self::validateMessage($message);
      }),
      new AuthorizationBehavior(function (object $message): void {
        self::authorizeMessage($message);
      }),
      new LoggingBehavior(function (string $message, array $context = []): void {
        self::pipelineLogger()->info($message, $context);
      }),
    ];
  }

  private static function pipelineLogger(): Logger
  {
    if (!self::$pipelineLogger) {
      self::$pipelineLogger = new Logger('pipeline');
    }

    return self::$pipelineLogger;
  }

  private static function beginTransaction(): void
  {
    $connection = Database::sharedConnection();
    if (!$connection->inTransaction()) {
      $connection->beginTransaction();
    }
  }

  private static function commitTransaction(): void
  {
    $connection = Database::sharedConnection();
    if ($connection->inTransaction()) {
      $connection->commit();
    }
  }

  private static function rollbackTransaction(): void
  {
    $connection = Database::sharedConnection();
    if ($connection->inTransaction()) {
      $connection->rollBack();
    }
  }

  private static function validateMessage(object $message): void
  {
    if ($message instanceof CreateUserCommand) {
      self::assertNonEmpty($message->name, 'User name is required.');
      self::assertEmail($message->email, 'User e-mail is invalid.');
      self::assertNonEmpty($message->password, 'User password is required.');
      self::assertMinLength($message->password, 6, 'User password must have at least 6 characters.');
      self::assertOptionalId($message->roleId, 'Role identifier is invalid.');
      return;
    }

    if ($message instanceof UpdateUserCommand) {
      self::assertId($message->userId, 'User identifier is invalid.');
      self::assertNonEmpty($message->name, 'User name is required.');
      self::assertEmail($message->email, 'User e-mail is invalid.');
      self::assertOptionalId($message->roleId, 'Role identifier is invalid.');

      if ($message->password !== null && $message->password !== '') {
        self::assertMinLength($message->password, 6, 'User password must have at least 6 characters.');
      }
      return;
    }

    if ($message instanceof DeleteUserCommand) {
      self::assertId($message->userId, 'User identifier is invalid.');
      return;
    }

    if ($message instanceof GetUserByIdQuery) {
      self::assertId($message->userId, 'User identifier is invalid.');
      return;
    }

    if ($message instanceof GetUserByEmailQuery) {
      self::assertEmail($message->email, 'User e-mail is invalid.');
      return;
    }

    if ($message instanceof CreateRoleCommand) {
      self::assertNonEmpty($message->name, 'Role name is required.');
      return;
    }

    if ($message instanceof UpdateRoleCommand) {
      self::assertId($message->roleId, 'Role identifier is invalid.');
      self::assertNonEmpty($message->name, 'Role name is required.');
      return;
    }

    if ($message instanceof DeleteRoleCommand) {
      self::assertId($message->roleId, 'Role identifier is invalid.');
      return;
    }

    if ($message instanceof CreatePermissionCommand) {
      if (trim($message->name) === '' && (trim($message->module) === '' || trim($message->action) === '')) {
        throw new InvalidArgumentException('Permission name is required.');
      }
      return;
    }

    if ($message instanceof UpdatePermissionCommand) {
      self::assertId($message->permissionId, 'Permission identifier is invalid.');
      if (trim($message->name) === '' && (trim($message->module) === '' || trim($message->action) === '')) {
        throw new InvalidArgumentException('Permission name is required.');
      }
      return;
    }

    if ($message instanceof DeletePermissionCommand) {
      self::assertId($message->permissionId, 'Permission identifier is invalid.');
      return;
    }

    if ($message instanceof AssignPermissionToRoleCommand || $message instanceof RemovePermissionFromRoleCommand) {
      self::assertId($message->roleId, 'Role identifier is invalid.');
      self::assertId($message->permissionId, 'Permission identifier is invalid.');
      return;
    }

    if ($message instanceof ListPermissionsByRoleQuery) {
      self::assertId($message->roleId, 'Role identifier is invalid.');
      return;
    }

    if ($message instanceof CreateVacancyCommand) {
      self::assertNonEmpty($message->title, 'Vacancy title is required.');
      self::assertNonEmpty($message->description, 'Vacancy description is required.');
      return;
    }

    if ($message instanceof UpdateVacancyCommand) {
      self::assertId($message->vacancyId, 'Vacancy identifier is invalid.');
      self::assertNonEmpty($message->title, 'Vacancy title is required.');
      self::assertNonEmpty($message->description, 'Vacancy description is required.');
      return;
    }

    if ($message instanceof DeleteVacancyCommand) {
      self::assertId($message->vacancyId, 'Vacancy identifier is invalid.');
      return;
    }

    if ($message instanceof ApplyToVacancyCommand) {
      self::assertId($message->userId, 'User identifier is invalid.');
      self::assertId($message->vacancyId, 'Vacancy identifier is invalid.');
      return;
    }

    if ($message instanceof GetVacancyByIdQuery) {
      self::assertId($message->vacancyId, 'Vacancy identifier is invalid.');
      return;
    }

    if ($message instanceof CountVacanciesQuery) {
      return;
    }

    if ($message instanceof ListAppliedVacancyIdsByUserQuery) {
      self::assertId($message->userId, 'User identifier is invalid.');
      return;
    }
  }

  private static function authorizeMessage(object $message): void
  {
    if (PHP_SAPI === 'cli') {
      return;
    }

    $userId = (string) ($_SESSION['user']['id'] ?? '');
    if ($userId === '') {
      throw new RuntimeException('Authentication is required to perform this action.');
    }

    $requiredPermissions = self::requiredPermissionsFor($message);
    if ($requiredPermissions === []) {
      return;
    }

    if (self::isAdminOnlyMessage($message) && !RoleManager::isAdmin($userId)) {
      throw new RuntimeException('This action is restricted to administrators.');
    }

    if (!self::hasAnyPermission($userId, $requiredPermissions)) {
      throw new RuntimeException(
        'Missing required permission. Expected one of: ' . implode(', ', $requiredPermissions)
      );
    }
  }

  /**
   * @return string[]
   */
  private static function requiredPermissionsFor(object $message): array
  {
    return match (true) {
      $message instanceof CreateUserCommand => ['user.create', 'user.edit'],
      $message instanceof UpdateUserCommand => ['user.edit'],
      $message instanceof DeleteUserCommand => ['user.delete'],
      $message instanceof ListUsersQuery => ['user.list'],
      $message instanceof GetUserByIdQuery => ['user.edit'],
      $message instanceof GetUserByEmailQuery => ['user.create', 'user.edit'],
      $message instanceof CountUsersQuery => ['user.list'],
      $message instanceof CreateRoleCommand => ['role.create', 'user.assign_role'],
      $message instanceof UpdateRoleCommand => ['role.edit', 'user.assign_role'],
      $message instanceof DeleteRoleCommand => ['role.delete', 'user.assign_role'],
      $message instanceof ListRolesQuery => ['role.list', 'user.assign_role'],
      $message instanceof CreatePermissionCommand => ['permission.create', 'user.assign_role'],
      $message instanceof UpdatePermissionCommand => ['permission.edit', 'user.assign_role'],
      $message instanceof DeletePermissionCommand => ['permission.delete', 'user.assign_role'],
      $message instanceof ListPermissionsQuery => ['permission.list', 'user.assign_role'],
      $message instanceof AssignPermissionToRoleCommand => ['role.assign_permission', 'user.assign_role'],
      $message instanceof RemovePermissionFromRoleCommand => ['role.assign_permission', 'user.assign_role'],
      $message instanceof ListPermissionsByRoleQuery => ['role.list', 'role.assign_permission', 'user.assign_role'],
      $message instanceof CreateVacancyCommand => ['vacancy.create'],
      $message instanceof UpdateVacancyCommand => ['vacancy.edit'],
      $message instanceof DeleteVacancyCommand => ['vacancy.delete'],
      $message instanceof CountVacanciesQuery => ['vacancy.view'],
      $message instanceof ListVacanciesQuery => ['vacancy.view'],
      $message instanceof GetVacancyByIdQuery => ['vacancy.view'],
      $message instanceof ApplyToVacancyCommand => ['vacancy.view'],
      default => [],
    };
  }

  /**
   * @param string[] $permissions
   */
  private static function hasAnyPermission(string $userId, array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if (RoleManager::hasPermission($userId, $permission)) {
        return true;
      }
    }

    return false;
  }

  private static function isAdminOnlyMessage(object $message): bool
  {
    return $message instanceof CreateUserCommand
      || $message instanceof UpdateUserCommand
      || $message instanceof DeleteUserCommand
      || $message instanceof ListUsersQuery
      || $message instanceof GetUserByIdQuery
      || $message instanceof GetUserByEmailQuery
      || $message instanceof CountUsersQuery
      || $message instanceof CreateRoleCommand
      || $message instanceof UpdateRoleCommand
      || $message instanceof DeleteRoleCommand
      || $message instanceof ListRolesQuery
      || $message instanceof CreatePermissionCommand
      || $message instanceof UpdatePermissionCommand
      || $message instanceof DeletePermissionCommand
      || $message instanceof ListPermissionsQuery
      || $message instanceof AssignPermissionToRoleCommand
      || $message instanceof RemovePermissionFromRoleCommand
      || $message instanceof ListPermissionsByRoleQuery;
  }

  private static function assertNonEmpty(string $value, string $message): void
  {
    if (trim($value) === '') {
      throw new InvalidArgumentException($message);
    }
  }

  private static function assertEmail(string $value, string $message): void
  {
    if (!filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException($message);
    }
  }

  private static function assertId(string $value, string $message): void
  {
    if (!IdValidator::isValid($value)) {
      throw new InvalidArgumentException($message);
    }
  }

  private static function assertOptionalId(?string $value, string $message): void
  {
    if ($value !== null && $value !== '' && !IdValidator::isValid($value)) {
      throw new InvalidArgumentException($message);
    }
  }

  private static function assertMinLength(string $value, int $length, string $message): void
  {
    if (strlen($value) < $length) {
      throw new InvalidArgumentException($message);
    }
  }
}

<?php

namespace App\Infrastructure\Container;

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
use App\Application\Exceptions\MessageAuthorizationException;
use App\Application\Exceptions\MessageValidationException;
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
use App\Util\IdValidator;
use App\Util\RoleManager;

final class MessagePipelineGuard
{
  private const USER_NAME_REQUIRED = 'User name is required.';
  private const USER_EMAIL_INVALID = 'User e-mail is invalid.';
  private const USER_PASSWORD_REQUIRED = 'User password is required.';
  private const USER_PASSWORD_MIN_LENGTH = 'User password must have at least 6 characters.';
  private const USER_IDENTIFIER_INVALID = 'User identifier is invalid.';
  private const ROLE_NAME_REQUIRED = 'Role name is required.';
  private const ROLE_IDENTIFIER_INVALID = 'Role identifier is invalid.';
  private const PERMISSION_NAME_REQUIRED = 'Permission name is required.';
  private const PERMISSION_IDENTIFIER_INVALID = 'Permission identifier is invalid.';
  private const VACANCY_TITLE_REQUIRED = 'Vacancy title is required.';
  private const VACANCY_DESCRIPTION_REQUIRED = 'Vacancy description is required.';
  private const VACANCY_IDENTIFIER_INVALID = 'Vacancy identifier is invalid.';

  /** @var array<class-string,list<string>> */
  private const REQUIRED_PERMISSIONS = [
    CreateUserCommand::class => ['user.create', 'user.edit'],
    UpdateUserCommand::class => ['user.edit'],
    DeleteUserCommand::class => ['user.delete'],
    ListUsersQuery::class => ['user.list'],
    GetUserByIdQuery::class => ['user.edit'],
    GetUserByEmailQuery::class => ['user.create', 'user.edit'],
    CountUsersQuery::class => ['user.list'],
    CreateRoleCommand::class => ['role.create', 'user.assign_role'],
    UpdateRoleCommand::class => ['role.edit', 'user.assign_role'],
    DeleteRoleCommand::class => ['role.delete', 'user.assign_role'],
    ListRolesQuery::class => ['role.list', 'user.assign_role'],
    CreatePermissionCommand::class => ['permission.create', 'user.assign_role'],
    UpdatePermissionCommand::class => ['permission.edit', 'user.assign_role'],
    DeletePermissionCommand::class => ['permission.delete', 'user.assign_role'],
    ListPermissionsQuery::class => ['permission.list', 'user.assign_role'],
    AssignPermissionToRoleCommand::class => ['role.assign_permission', 'user.assign_role'],
    RemovePermissionFromRoleCommand::class => ['role.assign_permission', 'user.assign_role'],
    ListPermissionsByRoleQuery::class => ['role.list', 'role.assign_permission', 'user.assign_role'],
    CreateVacancyCommand::class => ['vacancy.create'],
    UpdateVacancyCommand::class => ['vacancy.edit'],
    DeleteVacancyCommand::class => ['vacancy.delete'],
    CountVacanciesQuery::class => ['vacancy.view'],
    ListVacanciesQuery::class => ['vacancy.view'],
    GetVacancyByIdQuery::class => ['vacancy.view'],
    ApplyToVacancyCommand::class => ['vacancy.view'],
  ];

  /** @var array<class-string,true> */
  private const ADMIN_ONLY_MESSAGES = [
    CreateUserCommand::class => true,
    UpdateUserCommand::class => true,
    DeleteUserCommand::class => true,
    ListUsersQuery::class => true,
    GetUserByIdQuery::class => true,
    GetUserByEmailQuery::class => true,
    CountUsersQuery::class => true,
    CreateRoleCommand::class => true,
    UpdateRoleCommand::class => true,
    DeleteRoleCommand::class => true,
    ListRolesQuery::class => true,
    CreatePermissionCommand::class => true,
    UpdatePermissionCommand::class => true,
    DeletePermissionCommand::class => true,
    ListPermissionsQuery::class => true,
    AssignPermissionToRoleCommand::class => true,
    RemovePermissionFromRoleCommand::class => true,
    ListPermissionsByRoleQuery::class => true,
  ];

  public static function validate(object $message): void
  {
    $validator = self::validators()[$message::class] ?? null;
    if ($validator !== null) {
      $validator($message);
    }
  }

  public static function authorize(object $message): void
  {
    if (PHP_SAPI === 'cli') {
      return;
    }

    $userId = self::resolveSessionUserId();
    if ($userId === '') {
      throw new MessageAuthorizationException('Authentication is required to perform this action.');
    }

    $requiredPermissions = self::REQUIRED_PERMISSIONS[$message::class] ?? [];
    if ($requiredPermissions === []) {
      return;
    }

    if ((self::ADMIN_ONLY_MESSAGES[$message::class] ?? false) && !RoleManager::isAdmin($userId)) {
      throw new MessageAuthorizationException('This action is restricted to administrators.');
    }

    if (!self::hasAnyPermission($userId, $requiredPermissions)) {
      throw new MessageAuthorizationException(
        'Missing required permission. Expected one of: ' . implode(', ', $requiredPermissions)
      );
    }
  }

  /**
   * @return array<class-string,callable(object):void>
   */
  private static function validators(): array
  {
    $rolePermissionValidator = static function (object $message): void {
      /** @var AssignPermissionToRoleCommand|RemovePermissionFromRoleCommand $message */
      self::assertId($message->roleId, self::ROLE_IDENTIFIER_INVALID);
      self::assertId($message->permissionId, self::PERMISSION_IDENTIFIER_INVALID);
    };

    return [
      CreateUserCommand::class => static function (object $message): void {
        /** @var CreateUserCommand $message */
        self::assertNonEmpty($message->name, self::USER_NAME_REQUIRED);
        self::assertEmail($message->email, self::USER_EMAIL_INVALID);
        self::assertNonEmpty($message->password, self::USER_PASSWORD_REQUIRED);
        self::assertMinLength($message->password, 6, self::USER_PASSWORD_MIN_LENGTH);
        self::assertOptionalId($message->roleId, self::ROLE_IDENTIFIER_INVALID);
      },
      UpdateUserCommand::class => static function (object $message): void {
        /** @var UpdateUserCommand $message */
        self::assertId($message->userId, self::USER_IDENTIFIER_INVALID);
        self::assertNonEmpty($message->name, self::USER_NAME_REQUIRED);
        self::assertEmail($message->email, self::USER_EMAIL_INVALID);
        self::assertOptionalId($message->roleId, self::ROLE_IDENTIFIER_INVALID);

        if ($message->password !== null && $message->password !== '') {
          self::assertMinLength($message->password, 6, self::USER_PASSWORD_MIN_LENGTH);
        }
      },
      DeleteUserCommand::class => static function (object $message): void {
        /** @var DeleteUserCommand $message */
        self::assertId($message->userId, self::USER_IDENTIFIER_INVALID);
      },
      GetUserByIdQuery::class => static function (object $message): void {
        /** @var GetUserByIdQuery $message */
        self::assertId($message->userId, self::USER_IDENTIFIER_INVALID);
      },
      GetUserByEmailQuery::class => static function (object $message): void {
        /** @var GetUserByEmailQuery $message */
        self::assertEmail($message->email, self::USER_EMAIL_INVALID);
      },
      CreateRoleCommand::class => static function (object $message): void {
        /** @var CreateRoleCommand $message */
        self::assertNonEmpty($message->name, self::ROLE_NAME_REQUIRED);
      },
      UpdateRoleCommand::class => static function (object $message): void {
        /** @var UpdateRoleCommand $message */
        self::assertId($message->roleId, self::ROLE_IDENTIFIER_INVALID);
        self::assertNonEmpty($message->name, self::ROLE_NAME_REQUIRED);
      },
      DeleteRoleCommand::class => static function (object $message): void {
        /** @var DeleteRoleCommand $message */
        self::assertId($message->roleId, self::ROLE_IDENTIFIER_INVALID);
      },
      CreatePermissionCommand::class => static function (object $message): void {
        /** @var CreatePermissionCommand $message */
        if (trim($message->name) === '' && (trim($message->module) === '' || trim($message->action) === '')) {
          throw new MessageValidationException(self::PERMISSION_NAME_REQUIRED);
        }
      },
      UpdatePermissionCommand::class => static function (object $message): void {
        /** @var UpdatePermissionCommand $message */
        self::assertId($message->permissionId, self::PERMISSION_IDENTIFIER_INVALID);

        if (trim($message->name) === '' && (trim($message->module) === '' || trim($message->action) === '')) {
          throw new MessageValidationException(self::PERMISSION_NAME_REQUIRED);
        }
      },
      DeletePermissionCommand::class => static function (object $message): void {
        /** @var DeletePermissionCommand $message */
        self::assertId($message->permissionId, self::PERMISSION_IDENTIFIER_INVALID);
      },
      AssignPermissionToRoleCommand::class => $rolePermissionValidator,
      RemovePermissionFromRoleCommand::class => $rolePermissionValidator,
      ListPermissionsByRoleQuery::class => static function (object $message): void {
        /** @var ListPermissionsByRoleQuery $message */
        self::assertId($message->roleId, self::ROLE_IDENTIFIER_INVALID);
      },
      CreateVacancyCommand::class => static function (object $message): void {
        /** @var CreateVacancyCommand $message */
        self::assertNonEmpty($message->title, self::VACANCY_TITLE_REQUIRED);
        self::assertNonEmpty($message->description, self::VACANCY_DESCRIPTION_REQUIRED);
      },
      UpdateVacancyCommand::class => static function (object $message): void {
        /** @var UpdateVacancyCommand $message */
        self::assertId($message->vacancyId, self::VACANCY_IDENTIFIER_INVALID);
        self::assertNonEmpty($message->title, self::VACANCY_TITLE_REQUIRED);
        self::assertNonEmpty($message->description, self::VACANCY_DESCRIPTION_REQUIRED);
      },
      DeleteVacancyCommand::class => static function (object $message): void {
        /** @var DeleteVacancyCommand $message */
        self::assertId($message->vacancyId, self::VACANCY_IDENTIFIER_INVALID);
      },
      ApplyToVacancyCommand::class => static function (object $message): void {
        /** @var ApplyToVacancyCommand $message */
        self::assertId($message->userId, self::USER_IDENTIFIER_INVALID);
        self::assertId($message->vacancyId, self::VACANCY_IDENTIFIER_INVALID);
      },
      GetVacancyByIdQuery::class => static function (object $message): void {
        /** @var GetVacancyByIdQuery $message */
        self::assertId($message->vacancyId, self::VACANCY_IDENTIFIER_INVALID);
      },
      ListAppliedVacancyIdsByUserQuery::class => static function (object $message): void {
        /** @var ListAppliedVacancyIdsByUserQuery $message */
        self::assertId($message->userId, self::USER_IDENTIFIER_INVALID);
      },
    ];
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

  private static function resolveSessionUserId(): string
  {
    $sessionUser = $_SESSION['user'] ?? null;
    if (!is_array($sessionUser)) {
      return '';
    }

    $sessionUserId = $sessionUser['id'] ?? null;
    if (!is_scalar($sessionUserId)) {
      return '';
    }

    return (string) $sessionUserId;
  }

  private static function assertNonEmpty(string $value, string $message): void
  {
    if (trim($value) === '') {
      throw new MessageValidationException($message);
    }
  }

  private static function assertEmail(string $value, string $message): void
  {
    if (!filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
      throw new MessageValidationException($message);
    }
  }

  private static function assertId(string $value, string $message): void
  {
    if (!IdValidator::isValid($value)) {
      throw new MessageValidationException($message);
    }
  }

  private static function assertOptionalId(?string $value, string $message): void
  {
    if ($value !== null && $value !== '' && !IdValidator::isValid($value)) {
      throw new MessageValidationException($message);
    }
  }

  private static function assertMinLength(string $value, int $length, string $message): void
  {
    if (strlen($value) < $length) {
      throw new MessageValidationException($message);
    }
  }
}

<?php

namespace App\Application\Security;

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
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Queries\Users\SearchUsersQuery;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;

/**
 * Authorization policy for every command/query going through the buses.
 *
 * This is the single enforcement point: pages may hide buttons, but only this
 * class decides whether a use case can run.
 */
final class MessageAuthorizer
{
  public const ASSIGN_ROLE = 'user.assign_role';

  /**
   * The user needs at least ONE of the listed permissions.
   *
   * @var array<class-string,list<string>>
   */
  private const REQUIRED_PERMISSIONS = [
    CreateUserCommand::class => ['user.create', 'user.edit'],
    UpdateUserCommand::class => ['user.edit'],
    DeleteUserCommand::class => ['user.delete'],
    SearchUsersQuery::class => ['user.list'],
    GetUserByIdQuery::class => ['user.edit'],
    CreateRoleCommand::class => ['role.create', self::ASSIGN_ROLE],
    UpdateRoleCommand::class => ['role.edit', self::ASSIGN_ROLE],
    DeleteRoleCommand::class => ['role.delete', self::ASSIGN_ROLE],
    ListRolesQuery::class => ['role.list', self::ASSIGN_ROLE],
    CreatePermissionCommand::class => ['permission.create', self::ASSIGN_ROLE],
    UpdatePermissionCommand::class => ['permission.edit', self::ASSIGN_ROLE],
    DeletePermissionCommand::class => ['permission.delete', self::ASSIGN_ROLE],
    ListPermissionsQuery::class => ['permission.list', self::ASSIGN_ROLE],
    AssignPermissionToRoleCommand::class => ['role.assign_permission', self::ASSIGN_ROLE],
    RemovePermissionFromRoleCommand::class => ['role.assign_permission', self::ASSIGN_ROLE],
    CreateVacancyCommand::class => ['vacancy.create'],
    UpdateVacancyCommand::class => ['vacancy.edit'],
    DeleteVacancyCommand::class => ['vacancy.delete'],
    SearchVacanciesQuery::class => ['vacancy.view'],
    ListOpenVacanciesQuery::class => ['vacancy.view'],
    GetVacancyByIdQuery::class => ['vacancy.view'],
    ApplyToVacancyCommand::class => ['vacancy.view'],
  ];

  /** @var array<class-string,true> */
  private const ADMIN_ONLY = [
    CreateUserCommand::class => true,
    UpdateUserCommand::class => true,
    DeleteUserCommand::class => true,
    SearchUsersQuery::class => true,
    GetUserByIdQuery::class => true,
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
  ];

  public function __construct(
    private CurrentUserProvider $currentUser,
    private Authorization $authorization
  ) {
  }

  public function authorize(object $message): void
  {
    $user = $this->currentUser->currentUser();
    if ($user === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    if ((self::ADMIN_ONLY[$message::class] ?? false) && !$this->authorization->isAdmin($user->id)) {
      throw new ForbiddenException('Esta ação é restrita a administradores.');
    }

    $required = self::REQUIRED_PERMISSIONS[$message::class] ?? [];
    if ($required !== [] && !$this->authorization->canAny($user->id, $required)) {
      throw new ForbiddenException(
        'Você não tem permissão para executar esta ação (requer: ' . implode(' ou ', $required) . ').'
      );
    }

    if ($this->changesRole($message) && !$this->authorization->can($user->id, self::ASSIGN_ROLE)) {
      throw new ForbiddenException('Você não tem permissão para atribuir roles (requer: ' . self::ASSIGN_ROLE . ').');
    }
  }

  private function changesRole(object $message): bool
  {
    if ($message instanceof CreateUserCommand) {
      return $message->roleId !== null && $message->roleId !== '';
    }

    return $message instanceof UpdateUserCommand && $message->changeRole;
  }
}

<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\Commands\Permissions\UpdatePermissionCommand;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\Commands\Roles\DeleteRoleCommand;
use App\Application\Commands\Roles\UpdateRoleCommand;
use App\Application\DTOs\PermissionDto;
use App\Application\DTOs\RoleDto;
use App\Application\Features\Permissions\CreatePermissionHandler;
use App\Application\Features\Permissions\ListPermissionsHandler;
use App\Application\Features\Permissions\UpdatePermissionHandler;
use App\Application\Features\RolePermissions\AssignPermissionToRoleHandler;
use App\Application\Features\RolePermissions\RemovePermissionFromRoleHandler;
use App\Application\Features\Roles\CreateRoleHandler;
use App\Application\Features\Roles\DeleteRoleHandler;
use App\Application\Features\Roles\ListRolesHandler;
use App\Application\Features\Roles\UpdateRoleHandler;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Role\RoleId;
use App\Domain\User\EmailAddress;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryPermissionRepository;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemoryUserRepository;

final class AccessControlHandlersTest extends TestCase
{
  private InMemoryRoleRepository $roles;
  private InMemoryPermissionRepository $permissions;
  private InMemoryUserRepository $users;

  protected function setUp(): void
  {
    $this->roles = new InMemoryRoleRepository();
    $this->permissions = new InMemoryPermissionRepository($this->roles);
    $this->users = new InMemoryUserRepository();
  }

  public function testCreateAndListRoles(): void
  {
    $created = (new CreateRoleHandler($this->roles))->handle(new CreateRoleCommand('gestor', 'Gerencia vagas'));
    $this->assertInstanceOf(RoleDto::class, $created);

    $roles = (new ListRolesHandler($this->roles, $this->users))->handle(new ListRolesQuery());
    $this->assertCount(1, $roles);

    $filtered = (new ListRolesHandler($this->roles, $this->users))->handle(new ListRolesQuery('vagas'));
    $this->assertCount(1, $filtered);
    $this->assertCount(0, (new ListRolesHandler($this->roles, $this->users))->handle(new ListRolesQuery('xyz')));
  }

  public function testRoleNamesAreUnique(): void
  {
    $handler = new CreateRoleHandler($this->roles);
    $handler->handle(new CreateRoleCommand('gestor'));

    $this->expectException(AlreadyExistsException::class);
    $handler->handle(new CreateRoleCommand('gestor'));
  }

  public function testAdminRoleIsProtected(): void
  {
    $admin = (new CreateRoleHandler($this->roles))->handle(new CreateRoleCommand('admin'));

    $this->expectException(BusinessRuleViolationException::class);
    (new UpdateRoleHandler($this->roles))->handle(new UpdateRoleCommand($admin->id, 'root'));
  }

  public function testRoleInUseCannotBeDeletedAndUserCountIsReported(): void
  {
    $role = (new CreateRoleHandler($this->roles))->handle(new CreateRoleCommand('gestor'));
    $this->users->save(User::register(
      UserId::generate(),
      PersonName::fromString('Ana'),
      EmailAddress::fromString('ana@example.com'),
      PlainPassword::fromString('123456'),
      RoleId::fromString($role->id)
    ));

    $listed = (new ListRolesHandler($this->roles, $this->users))->handle(new ListRolesQuery());
    $this->assertSame(1, $listed[0]->userCount);

    $this->expectException(BusinessRuleViolationException::class);
    (new DeleteRoleHandler($this->roles, $this->users))->handle(new DeleteRoleCommand($role->id));
  }

  public function testPermissionsCreateListAndUpdate(): void
  {
    $created = (new CreatePermissionHandler($this->permissions))->handle(
      new CreatePermissionCommand('', 'Pode listar usuários', 'user', 'list')
    );
    $this->assertSame('user.list', $created->name);

    (new CreatePermissionHandler($this->permissions))->handle(new CreatePermissionCommand('vacancy.view'));

    $list = new ListPermissionsHandler($this->permissions);
    $this->assertContainsOnlyInstancesOf(PermissionDto::class, $list->handle(new ListPermissionsQuery()));
    $this->assertCount(1, $list->handle(new ListPermissionsQuery('user')));
    $this->assertCount(1, $list->handle(new ListPermissionsQuery(null, 'listar')));

    $this->expectException(AlreadyExistsException::class);
    (new UpdatePermissionHandler($this->permissions))->handle(
      new UpdatePermissionCommand($created->id, 'vacancy.view')
    );
  }

  public function testAssignAndRemovePermissionFromRole(): void
  {
    $role = (new CreateRoleHandler($this->roles))->handle(new CreateRoleCommand('gestor'));
    $permission = (new CreatePermissionHandler($this->permissions))->handle(new CreatePermissionCommand('vacancy.view'));

    (new AssignPermissionToRoleHandler($this->roles, $this->permissions))->handle(
      new AssignPermissionToRoleCommand($role->id, $permission->id)
    );
    $this->assertSame(['vacancy.view'], $this->permissions->namesGrantedToRole(RoleId::fromString($role->id)));

    (new RemovePermissionFromRoleHandler($this->roles))->handle(
      new RemovePermissionFromRoleCommand($role->id, $permission->id)
    );
    $this->assertSame([], $this->permissions->namesGrantedToRole(RoleId::fromString($role->id)));
  }
}

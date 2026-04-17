<?php

namespace Tests\Unit\Application\Features;

use App\Application\Commands\Permissions\CreatePermissionCommand;
use App\Application\Commands\RolePermissions\AssignPermissionToRoleCommand;
use App\Application\Commands\RolePermissions\RemovePermissionFromRoleCommand;
use App\Application\Commands\Roles\CreateRoleCommand;
use App\Application\DTOs\PermissionDto;
use App\Application\DTOs\RoleDto;
use App\Application\Features\Permissions\CreatePermissionHandler;
use App\Application\Features\Permissions\ListPermissionsHandler;
use App\Application\Features\RolePermissions\AssignPermissionToRoleHandler;
use App\Application\Features\RolePermissions\ListPermissionsByRoleHandler;
use App\Application\Features\RolePermissions\RemovePermissionFromRoleHandler;
use App\Application\Features\Roles\CreateRoleHandler;
use App\Application\Features\Roles\ListRolesHandler;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Queries\RolePermissions\ListPermissionsByRoleQuery;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Service\AccessControlService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePermissionRepository;
use Tests\Support\FakeRolePermissionRepository;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;

class AccessControlHandlersTest extends TestCase
{
  public function testRoleHandlersCreateAndListRoles(): void
  {
    $service = $this->makeService();

    $createHandler = new CreateRoleHandler($service);
    $createResult = $createHandler->handle(new CreateRoleCommand('admin-extra', 'Perfil administrativo extra'));

    $this->assertTrue($createResult['ok']);

    $listHandler = new ListRolesHandler($service);
    $roles = $listHandler->handle(new ListRolesQuery());

    $this->assertNotEmpty($roles);
    $this->assertContainsOnlyInstancesOf(RoleDto::class, $roles);
  }

  public function testPermissionHandlersCreateAndListPermissions(): void
  {
    $service = $this->makeService();

    $createHandler = new CreatePermissionHandler($service);
    $createResult = $createHandler->handle(new CreatePermissionCommand(
      '',
      'Pode listar usuários',
      'user',
      'list'
    ));

    $this->assertTrue($createResult['ok']);

    $listHandler = new ListPermissionsHandler($service);
    $permissions = $listHandler->handle(new ListPermissionsQuery('user'));

    $this->assertNotEmpty($permissions);
    $this->assertContainsOnlyInstancesOf(PermissionDto::class, $permissions);
    $this->assertSame('user', $permissions[0]->module);
  }

  public function testRolePermissionHandlersListAssignAndRemove(): void
  {
    $roleRepository = new FakeRoleRepository();
    $permissionRepository = new FakePermissionRepository();
    $rolePermissionRepository = new FakeRolePermissionRepository();
    $userRepository = new FakeUserRepository();

    $service = new AccessControlService(
      $roleRepository,
      $permissionRepository,
      $rolePermissionRepository,
      $userRepository
    );

    $createRoleHandler = new CreateRoleHandler($service);
    $roleResult = $createRoleHandler->handle(new CreateRoleCommand('gestor-extra', 'Role para testes'));
    $roleId = (string) ($roleResult['role']->id ?? '');

    $createPermissionHandler = new CreatePermissionHandler($service);
    $permissionResult = $createPermissionHandler->handle(new CreatePermissionCommand(
      'role.assign_permission',
      'Vincular permissões em roles',
      'role',
      'assign_permission'
    ));
    $permissionId = (string) ($permissionResult['permission']->id ?? '');

    $assignHandler = new AssignPermissionToRoleHandler($service);
    $assignResult = $assignHandler->handle(new AssignPermissionToRoleCommand($roleId, $permissionId));
    $this->assertTrue($assignResult['ok']);

    $rolePermissionRepository->setPermissions($roleId, ['role.assign_permission']);

    $listByRoleHandler = new ListPermissionsByRoleHandler($service);
    $permissions = $listByRoleHandler->handle(new ListPermissionsByRoleQuery($roleId));

    $this->assertNotEmpty($permissions);
    $this->assertContainsOnlyInstancesOf(PermissionDto::class, $permissions);
    $this->assertSame('role.assign_permission', $permissions[0]->name);

    $removeHandler = new RemovePermissionFromRoleHandler($service);
    $removeResult = $removeHandler->handle(new RemovePermissionFromRoleCommand($roleId, $permissionId));
    $this->assertTrue($removeResult['ok']);
  }

  private function makeService(): AccessControlService
  {
    return new AccessControlService(
      new FakeRoleRepository(),
      new FakePermissionRepository(),
      new FakeRolePermissionRepository(),
      new FakeUserRepository()
    );
  }
}

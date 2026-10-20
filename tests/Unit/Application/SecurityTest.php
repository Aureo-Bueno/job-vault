<?php

namespace Tests\Unit\Application;

use App\Application\Commands\Users\CreateUserCommand;
use App\Application\Commands\Users\DeleteUserCommand;
use App\Application\Commands\Users\UpdateUserCommand;
use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Queries\Applications\ListMyAppliedVacancyIdsQuery;
use App\Application\Security\MessageAuthorizer;
use App\Application\Security\RoleBasedAuthorization;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\User\EmailAddress;
use App\Domain\User\PersonName;
use App\Domain\User\PlainPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAuthorization;
use Tests\Support\FakeCurrentUser;
use Tests\Support\InMemoryPermissionRepository;
use Tests\Support\InMemoryRoleRepository;
use Tests\Support\InMemoryUserRepository;

final class SecurityTest extends TestCase
{
  public function testRoleBasedAuthorizationResolvesPermissionsThroughRole(): void
  {
    $users = new InMemoryUserRepository();
    $roles = new InMemoryRoleRepository();
    $permissions = new InMemoryPermissionRepository($roles);

    $permission = Permission::define(PermissionId::generate(), 'user.list');
    $permissions->save($permission);
    $role = Role::create(RoleId::generate(), RoleName::admin());
    $role->grant($permission->id());
    $roles->save($role);

    $user = User::register(
      UserId::generate(),
      PersonName::fromString('Admin'),
      EmailAddress::fromString('admin@example.com'),
      PlainPassword::fromString('123456'),
      $role->id()
    );
    $users->save($user);

    $authorization = new RoleBasedAuthorization($users, $roles, $permissions);
    $id = $user->id()->value();

    $this->assertTrue($authorization->can($id, 'user.list'));
    $this->assertFalse($authorization->can($id, 'user.delete'));
    $this->assertTrue($authorization->isAdmin($id));
    $this->assertSame('admin', $authorization->roleName($id));
    $this->assertFalse($authorization->can('unknown', 'user.list'));

    $this->expectException(ForbiddenException::class);
    $authorization->ensure($id, 'user.delete');
  }

  public function testAuthorizerRequiresAuthentication(): void
  {
    $authorizer = new MessageAuthorizer(new FakeCurrentUser(), new FakeAuthorization(['user.delete']));

    $this->expectException(UnauthorizedException::class);
    $authorizer->authorize(new ListMyAppliedVacancyIdsQuery());
  }

  public function testAuthorizerChecksPermission(): void
  {
    $authorizer = new MessageAuthorizer(FakeCurrentUser::as('1'), new FakeAuthorization(['vacancy.create'], false));
    $authorizer->authorize(new CreateVacancyCommand('T', 'D'));

    $this->expectException(ForbiddenException::class);
    $this->expectExceptionMessage('user.delete');
    (new MessageAuthorizer(FakeCurrentUser::as('1'), new FakeAuthorization(['user.list'])))
      ->authorize(new DeleteUserCommand('2'));
  }

  public function testAuthorizerEnforcesAdminOnlyMessages(): void
  {
    $authorizer = new MessageAuthorizer(FakeCurrentUser::as('1'), new FakeAuthorization(['user.delete'], false));

    $this->expectException(ForbiddenException::class);
    $this->expectExceptionMessage('administradores');
    $authorizer->authorize(new DeleteUserCommand('2'));
  }

  public function testChangingRoleRequiresAssignRolePermission(): void
  {
    $authorizer = new MessageAuthorizer(FakeCurrentUser::as('1'), new FakeAuthorization(['user.create', 'user.edit']));
    $authorizer->authorize(new CreateUserCommand('Ana', 'ana@example.com', '123456'));
    $authorizer->authorize(new UpdateUserCommand('2', 'Ana', 'ana@example.com'));

    $this->expectException(ForbiddenException::class);
    $authorizer->authorize(new UpdateUserCommand('2', 'Ana', 'ana@example.com', null, '3', true));
  }
}

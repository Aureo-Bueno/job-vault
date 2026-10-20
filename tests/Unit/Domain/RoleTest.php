<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Permission\PermissionId;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
  public function testGrantAndRevokePermissions(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $permission = PermissionId::generate();

    $role->grant($permission);
    $this->assertTrue($role->hasPermission($permission));

    $role->revoke($permission);
    $this->assertFalse($role->hasPermission($permission));
  }

  public function testCannotGrantTwice(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $permission = PermissionId::generate();
    $role->grant($permission);

    $this->expectException(AlreadyExistsException::class);
    $role->grant($permission);
  }

  public function testCannotRevokeMissingPermission(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));

    $this->expectException(BusinessRuleViolationException::class);
    $role->revoke(PermissionId::generate());
  }

  public function testAdminCannotBeRenamed(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::admin());

    $this->expectException(BusinessRuleViolationException::class);
    $role->rename(RoleName::fromString('root'));
  }

  public function testRegularRoleCanBeRenamed(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $role->rename(RoleName::fromString('manager'));

    $this->assertSame('manager', $role->name()->value());
  }

  public function testAdminCannotBeDeleted(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::admin());

    $this->expectException(BusinessRuleViolationException::class);
    $role->ensureCanBeDeleted(0);
  }

  public function testRoleInUseCannotBeDeleted(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));

    $this->expectException(BusinessRuleViolationException::class);
    $role->ensureCanBeDeleted(2);
  }

  public function testUnusedRoleCanBeDeleted(): void
  {
    $role = Role::create(RoleId::generate(), RoleName::fromString('gestor'));
    $role->ensureCanBeDeleted(0);

    $this->addToAssertionCount(1);
  }
}

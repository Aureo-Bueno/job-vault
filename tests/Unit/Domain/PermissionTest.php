<?php

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
  public function testNameIsComposedFromNormalizedModuleAndAction(): void
  {
    $permission = Permission::define(PermissionId::generate(), '', 'Listar', ' User ', 'Assign Role');

    $this->assertSame('user.assign_role', $permission->name()->value());
    $this->assertSame('user', $permission->module());
    $this->assertSame('assign_role', $permission->action());
  }

  public function testExplicitNameWins(): void
  {
    $permission = Permission::define(PermissionId::generate(), 'vacancy.publish', '', 'vacancy', 'publish');

    $this->assertSame('vacancy.publish', $permission->name()->value());
  }

  public function testNameOrModuleAndActionAreRequired(): void
  {
    $this->expectException(InvalidValueException::class);
    Permission::define(PermissionId::generate(), '', '', 'user', '');
  }

  public function testRedefine(): void
  {
    $permission = Permission::define(PermissionId::generate(), 'user.list');
    $permission->redefine('', 'Criar', 'user', 'create');

    $this->assertSame('user.create', $permission->name()->value());
    $this->assertTrue($permission->belongsToModule('USER'));
  }
}

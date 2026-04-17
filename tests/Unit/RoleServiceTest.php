<?php

namespace Tests\Unit;

use App\Application\Service\RoleService;
use App\Domain\Model\Role;
use App\Domain\Model\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeRolePermissionRepository;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;

class RoleServiceTest extends TestCase
{
  public function testHasPermission(): void
  {
    $userRepo = new FakeUserRepository();
    $roleRepo = new FakeRoleRepository();
    $permRepo = new FakeRolePermissionRepository();

    $role = new Role('1', 'admin', '', '');
    $roleRepo->add($role);
    $permRepo->setPermissions('1', ['usuario.listar']);

    $user = new User(null, 'Admin', 'admin@exemplo.com', 'hash', '1');
    $userRepo->create($user);

    $service = new RoleService($userRepo, $roleRepo, $permRepo);

    $this->assertTrue($service->hasPermission((string) $user->id, 'usuario.listar'));
    $this->assertFalse($service->hasPermission((string) $user->id, 'usuario.deletar'));
  }
}

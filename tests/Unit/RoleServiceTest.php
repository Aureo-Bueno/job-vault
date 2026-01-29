<?php

namespace Tests\Unit;

use App\Application\Service\RoleService;
use App\Domain\Model\Role;
use App\Domain\Model\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeRolePermissionRepository;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUsuarioRepository;

class RoleServiceTest extends TestCase
{
  public function testHasPermission(): void
  {
    $userRepo = new FakeUsuarioRepository();
    $roleRepo = new FakeRoleRepository();
    $permRepo = new FakeRolePermissionRepository();

    $role = new Role(1, 'admin', '', '');
    $roleRepo->add($role);
    $permRepo->setPermissions(1, ['usuario.listar']);

    $usuario = new Usuario(null, 'Admin', 'admin@exemplo.com', 'hash', 1);
    $userRepo->create($usuario);

    $service = new RoleService($userRepo, $roleRepo, $permRepo);

    $this->assertTrue($service->hasPermission($usuario->id, 'usuario.listar'));
    $this->assertFalse($service->hasPermission($usuario->id, 'usuario.deletar'));
  }
}

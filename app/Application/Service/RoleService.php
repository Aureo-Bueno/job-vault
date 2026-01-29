<?php

namespace App\Application\Service;

use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UsuarioRepositoryInterface;

class RoleService
{
  private UsuarioRepositoryInterface $usuarioRepository;
  private RoleRepositoryInterface $roleRepository;
  private RolePermissionRepositoryInterface $rolePermissionRepository;

  public function __construct(
    UsuarioRepositoryInterface $usuarioRepository,
    RoleRepositoryInterface $roleRepository,
    RolePermissionRepositoryInterface $rolePermissionRepository
  ) {
    $this->usuarioRepository = $usuarioRepository;
    $this->roleRepository = $roleRepository;
    $this->rolePermissionRepository = $rolePermissionRepository;
  }

  public function hasPermission(int $userId, string $permissionName): bool
  {
    $usuario = $this->usuarioRepository->findById($userId);

    if (!$usuario || $usuario->roleId === null) {
      return false;
    }

    return $this->rolePermissionRepository->roleHasPermission($usuario->roleId, $permissionName);
  }

  /** @return string[] */
  public function getPermissionsByRole(int $roleId): array
  {
    $permissions = $this->rolePermissionRepository->getPermissionsByRoleId($roleId);

    $permissionNames = [];
    foreach ($permissions as $permission) {
      $permissionNames[] = $permission->nome;
    }

    return $permissionNames;
  }

  public function getUserRole(int $userId): ?Role
  {
    $usuario = $this->usuarioRepository->findById($userId);

    if (!$usuario || $usuario->roleId === null) {
      return null;
    }

    return $this->roleRepository->findById($usuario->roleId);
  }

  /** @return Role[] */
  public function listRoles(): array
  {
    return $this->roleRepository->findAll();
  }

  public function isAdmin(int $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->nome === 'admin';
  }

  public function isGestor(int $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->nome === 'gestor';
  }

  public function requirePermission(int $userId, string $permissionName): void
  {
    if (!$this->hasPermission($userId, $permissionName)) {
      http_response_code(403);
      die('Você não tem permissão para acessar este recurso.');
    }
  }
}

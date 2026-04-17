<?php

namespace App\Application\Service;

use App\Domain\Model\Permission;
use App\Domain\Model\Role;
use App\Domain\Repository\PermissionRepositoryInterface;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Util\IdValidator;

/**
 * Handles administration of roles, permissions, and role-permission assignments.
 *
 * This service centralizes validation and persistence orchestration for access-control
 * operations used by the admin panel.
 */
final class AccessControlService
{
  private RoleRepositoryInterface $roleRepository;
  private PermissionRepositoryInterface $permissionRepository;
  private RolePermissionRepositoryInterface $rolePermissionRepository;
  private UserRepositoryInterface $userRepository;

  public function __construct(
    RoleRepositoryInterface $roleRepository,
    PermissionRepositoryInterface $permissionRepository,
    RolePermissionRepositoryInterface $rolePermissionRepository,
    UserRepositoryInterface $userRepository
  ) {
    $this->roleRepository = $roleRepository;
    $this->permissionRepository = $permissionRepository;
    $this->rolePermissionRepository = $rolePermissionRepository;
    $this->userRepository = $userRepository;
  }

  /**
   * Returns all roles sorted by repository strategy.
   *
   * @return Role[]
   */
  public function listRoles(): array
  {
    return $this->roleRepository->findAll();
  }

  /**
   * Returns all permissions sorted by repository strategy.
   *
   * @return Permission[]
   */
  public function listPermissions(): array
  {
    return $this->permissionRepository->findAll();
  }

  /**
   * Returns all permissions currently assigned to a role.
   *
   * @param string $roleId Role identifier.
   * @return Permission[]
   */
  public function listPermissionsByRole(string $roleId): array
  {
    return $this->rolePermissionRepository->getPermissionsByRoleId($roleId);
  }

  /**
   * Creates a new role after validating required fields and uniqueness.
   *
   * @param string $name Role name.
   * @param string $description Optional role description.
   * @return array{ok:bool,error:?string,role:?Role}
   */
  public function createRole(string $name, string $description = ''): array
  {
    $normalizedName = trim($name);
    if ($normalizedName === '') {
      return ['ok' => false, 'error' => 'Nome da role é obrigatório.', 'role' => null];
    }

    if ($this->roleRepository->findByName($normalizedName)) {
      return ['ok' => false, 'error' => 'Já existe uma role com esse nome.', 'role' => null];
    }

    $role = new Role(null, $normalizedName, trim($description));
    $id = $this->roleRepository->create($role);
    if ($id === null) {
      return ['ok' => false, 'error' => 'Não foi possível criar a role.', 'role' => null];
    }

    $role->id = $id;
    return ['ok' => true, 'error' => null, 'role' => $role];
  }

  /**
   * Updates an existing role with validation for identity, uniqueness and invariants.
   *
   * @param string $id Role identifier.
   * @param string $name New role name.
   * @param string $description New role description.
   * @return array{ok:bool,error:?string}
   */
  public function updateRole(string $id, string $name, string $description = ''): array
  {
    if (!IdValidator::isValid($id)) {
      return ['ok' => false, 'error' => 'Role inválida.'];
    }

    $role = $this->roleRepository->findById($id);
    if (!$role) {
      return ['ok' => false, 'error' => 'Role não encontrada.'];
    }

    $normalizedName = trim($name);
    if ($normalizedName === '') {
      return ['ok' => false, 'error' => 'Nome da role é obrigatório.'];
    }

    if ($role->name === 'admin' && $normalizedName !== 'admin') {
      return ['ok' => false, 'error' => 'A role admin não pode ser renomeada.'];
    }

    $existingRole = $this->roleRepository->findByName($normalizedName);
    if ($existingRole && $existingRole->id !== $role->id) {
      return ['ok' => false, 'error' => 'Já existe uma role com esse nome.'];
    }

    $role->name = $normalizedName;
    $role->description = trim($description);

    if (!$this->roleRepository->update($role)) {
      return ['ok' => false, 'error' => 'Não foi possível atualizar a role.'];
    }

    return ['ok' => true, 'error' => null];
  }

  /**
   * Deletes a role when it is safe to do so.
   *
   * Business guards:
   * - the role must exist;
   * - the built-in admin role cannot be removed;
   * - no user can still reference the role.
   *
   * @param string $id Role identifier.
   * @return array{ok:bool,error:?string}
   */
  public function deleteRole(string $id): array
  {
    if (!IdValidator::isValid($id)) {
      return ['ok' => false, 'error' => 'Role inválida.'];
    }

    $role = $this->roleRepository->findById($id);
    if (!$role) {
      return ['ok' => false, 'error' => 'Role não encontrada.'];
    }

    if ($role->name === 'admin') {
      return ['ok' => false, 'error' => 'A role admin não pode ser removida.'];
    }

    $usersWithRole = $this->userRepository->count('role_id = :role_id', ['role_id' => $id]);
    if ($usersWithRole > 0) {
      return ['ok' => false, 'error' => 'Remova a role dos usuários antes de excluir.'];
    }

    if (!$this->roleRepository->delete($id)) {
      return ['ok' => false, 'error' => 'Não foi possível excluir a role.'];
    }

    return ['ok' => true, 'error' => null];
  }

  /**
   * Creates a permission with optional automatic name composition (`module.action`).
   *
   * @param string $name Permission name. If empty, module+action are used.
   * @param string $description Optional permission description.
   * @param string $module Optional module identifier.
   * @param string $action Optional action identifier.
   * @return array{ok:bool,error:?string,permission:?Permission}
   */
  public function createPermission(
    string $name,
    string $description = '',
    string $module = '',
    string $action = ''
  ): array {
    $normalizedModule = $this->normalizeIdentifier($module);
    $normalizedAction = $this->normalizeIdentifier($action);

    $normalizedName = trim($name);
    if ($normalizedName === '' && $normalizedModule !== '' && $normalizedAction !== '') {
      $normalizedName = $normalizedModule . '.' . $normalizedAction;
    }

    if ($normalizedName === '') {
      return ['ok' => false, 'error' => 'Nome da permissão é obrigatório.', 'permission' => null];
    }

    if ($this->permissionRepository->findByName($normalizedName)) {
      return ['ok' => false, 'error' => 'Já existe uma permissão com esse nome.', 'permission' => null];
    }

    $permission = new Permission(
      null,
      $normalizedName,
      trim($description),
      $normalizedModule,
      $normalizedAction
    );

    $id = $this->permissionRepository->create($permission);
    if ($id === null) {
      return ['ok' => false, 'error' => 'Não foi possível criar a permissão.', 'permission' => null];
    }

    $permission->id = $id;
    return ['ok' => true, 'error' => null, 'permission' => $permission];
  }

  /**
   * Updates a permission and keeps the name unique across the system.
   *
   * @param string $id Permission identifier.
   * @param string $name New permission name. If empty, module+action are used.
   * @param string $description New description.
   * @param string $module Module identifier.
   * @param string $action Action identifier.
   * @return array{ok:bool,error:?string}
   */
  public function updatePermission(
    string $id,
    string $name,
    string $description = '',
    string $module = '',
    string $action = ''
  ): array {
    if (!IdValidator::isValid($id)) {
      return ['ok' => false, 'error' => 'Permissão inválida.'];
    }

    $permission = $this->permissionRepository->findById($id);
    if (!$permission) {
      return ['ok' => false, 'error' => 'Permissão não encontrada.'];
    }

    $normalizedModule = $this->normalizeIdentifier($module);
    $normalizedAction = $this->normalizeIdentifier($action);

    $normalizedName = trim($name);
    if ($normalizedName === '' && $normalizedModule !== '' && $normalizedAction !== '') {
      $normalizedName = $normalizedModule . '.' . $normalizedAction;
    }

    if ($normalizedName === '') {
      return ['ok' => false, 'error' => 'Nome da permissão é obrigatório.'];
    }

    $existingPermission = $this->permissionRepository->findByName($normalizedName);
    if ($existingPermission && $existingPermission->id !== $permission->id) {
      return ['ok' => false, 'error' => 'Já existe uma permissão com esse nome.'];
    }

    $permission->name = $normalizedName;
    $permission->description = trim($description);
    $permission->module = $normalizedModule;
    $permission->action = $normalizedAction;

    if (!$this->permissionRepository->update($permission)) {
      return ['ok' => false, 'error' => 'Não foi possível atualizar a permissão.'];
    }

    return ['ok' => true, 'error' => null];
  }

  /**
   * Deletes a permission.
   *
   * @param string $id Permission identifier.
   * @return array{ok:bool,error:?string}
   */
  public function deletePermission(string $id): array
  {
    if (!IdValidator::isValid($id)) {
      return ['ok' => false, 'error' => 'Permissão inválida.'];
    }

    $permission = $this->permissionRepository->findById($id);
    if (!$permission) {
      return ['ok' => false, 'error' => 'Permissão não encontrada.'];
    }

    if (!$this->permissionRepository->delete($id)) {
      return ['ok' => false, 'error' => 'Não foi possível excluir a permissão.'];
    }

    return ['ok' => true, 'error' => null];
  }

  /**
   * Assigns a permission to a role.
   *
   * @param string $roleId Role identifier.
   * @param string $permissionId Permission identifier.
   * @return array{ok:bool,error:?string}
   */
  public function assignPermissionToRole(string $roleId, string $permissionId): array
  {
    if (!IdValidator::isValid($roleId) || !IdValidator::isValid($permissionId)) {
      return ['ok' => false, 'error' => 'Role ou permissão inválida.'];
    }

    if (!$this->roleRepository->findById($roleId)) {
      return ['ok' => false, 'error' => 'Role não encontrada.'];
    }

    if (!$this->permissionRepository->findById($permissionId)) {
      return ['ok' => false, 'error' => 'Permissão não encontrada.'];
    }

    $assigned = $this->rolePermissionRepository->assignPermissionToRole($roleId, $permissionId);
    if (!$assigned) {
      return ['ok' => false, 'error' => 'Permissão já vinculada à role.'];
    }

    return ['ok' => true, 'error' => null];
  }

  /**
   * Removes a permission from a role.
   *
   * @param string $roleId Role identifier.
   * @param string $permissionId Permission identifier.
   * @return array{ok:bool,error:?string}
   */
  public function removePermissionFromRole(string $roleId, string $permissionId): array
  {
    if (!IdValidator::isValid($roleId) || !IdValidator::isValid($permissionId)) {
      return ['ok' => false, 'error' => 'Role ou permissão inválida.'];
    }

    $this->rolePermissionRepository->removePermissionFromRole($roleId, $permissionId);
    return ['ok' => true, 'error' => null];
  }

  /**
   * Normalizes free-form identifiers to a safe, lowercase token.
   *
   * Allowed characters: `a-z`, `0-9`, `.`, `_`, `-`.
   */
  private function normalizeIdentifier(string $value): string
  {
    $trimmed = trim($value);
    if ($trimmed === '') {
      return '';
    }

    $normalized = strtolower($trimmed);
    $normalized = preg_replace('/[^a-z0-9._-]+/', '_', $normalized) ?? '';
    return trim($normalized, '_');
  }
}

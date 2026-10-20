<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: default roles, permissions and their grants.
 */
return new class extends Migration {
  /** @var array<string,string> name => description */
  private const ROLES = [
    'admin' => 'Administrador com acesso total',
    'gestor' => 'Gerenciador de vagas',
    'usuario' => 'Usuário padrão com permissões limitadas',
  ];

  /** @var array<string,string> name (module.action) => description */
  private const PERMISSIONS = [
    'vacancy.create' => 'Criar novas vagas',
    'vacancy.edit' => 'Editar vagas existentes',
    'vacancy.delete' => 'Deletar vagas',
    'vacancy.view' => 'Visualizar vagas',
    'vacancy.publish' => 'Publicar/Ativar vagas',
    'vacancy.manage' => 'Gerenciar todas as vagas',
    'user.list' => 'Listar usuários',
    'user.create' => 'Criar usuários',
    'user.edit' => 'Editar usuários',
    'user.delete' => 'Deletar usuários',
    'user.assign_role' => 'Atribuir roles a usuários',
    'role.list' => 'Listar roles',
    'role.create' => 'Criar roles',
    'role.edit' => 'Editar roles',
    'role.delete' => 'Remover roles',
    'role.assign_permission' => 'Vincular permissões em roles',
    'permission.list' => 'Listar permissões',
    'permission.create' => 'Criar permissões',
    'permission.edit' => 'Editar permissões',
    'permission.delete' => 'Remover permissões',
  ];

  /** @var array<string,list<string>|'*'> role => granted permissions */
  private const GRANTS = [
    'admin' => '*',
    'gestor' => [
      'vacancy.create', 'vacancy.edit', 'vacancy.delete',
      'vacancy.view', 'vacancy.publish', 'vacancy.manage',
      'user.list',
    ],
    'usuario' => ['vacancy.view'],
  ];

  public function up(Database $db): void
  {
    foreach (self::ROLES as $name => $description) {
      $db->run('INSERT INTO `roles` (`id`, `name`, `description`) VALUES (UUID(), ?, ?)', [$name, $description]);
    }

    foreach (self::PERMISSIONS as $name => $description) {
      [$module, $action] = explode('.', $name, 2);
      $db->run(
        'INSERT INTO `permissions` (`id`, `name`, `description`, `module`, `action`) VALUES (UUID(), ?, ?, ?, ?)',
        [$name, $description, $module, $action]
      );
    }

    foreach (self::GRANTS as $role => $permissions) {
      $permissions = $permissions === '*' ? array_keys(self::PERMISSIONS) : $permissions;
      $placeholders = implode(', ', array_fill(0, count($permissions), '?'));
      $db->run(
        'INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`)'
        . ' SELECT UUID(), r.id, p.id FROM `roles` r JOIN `permissions` p ON p.name IN (' . $placeholders . ')'
        . ' WHERE r.name = ?',
        [...$permissions, $role]
      );
    }
  }

  public function down(Database $db): void
  {
    // role_permissions rows go away through ON DELETE CASCADE.
    $roles = array_keys(self::ROLES);
    $db->run(
      'DELETE FROM `roles` WHERE `name` IN (' . implode(', ', array_fill(0, count($roles), '?')) . ')',
      $roles
    );

    $permissions = array_keys(self::PERMISSIONS);
    $db->run(
      'DELETE FROM `permissions` WHERE `name` IN (' . implode(', ', array_fill(0, count($permissions), '?')) . ')',
      $permissions
    );
  }
};

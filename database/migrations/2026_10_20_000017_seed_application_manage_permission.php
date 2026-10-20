<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: `application.manage` permission (move candidates through the pipeline, write notes),
 * granted to the admin and gestor roles.
 */
return new class extends Migration {
  private const PERMISSION = 'application.manage';
  private const ROLES = ['admin', 'gestor'];

  public function up(Database $db): void
  {
    $db->run(
      'INSERT INTO `permissions` (`id`, `name`, `description`, `module`, `action`) VALUES (UUID(), ?, ?, ?, ?)',
      [self::PERMISSION, 'Gerenciar o pipeline de candidaturas', 'application', 'manage']
    );

    $db->run(
      'INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`)'
      . ' SELECT UUID(), r.id, p.id FROM `roles` r JOIN `permissions` p ON p.name = ?'
      . ' WHERE r.name IN (?, ?)',
      [self::PERMISSION, ...self::ROLES]
    );
  }

  public function down(Database $db): void
  {
    // Grants are removed through ON DELETE CASCADE.
    $db->run('DELETE FROM `permissions` WHERE `name` = ?', [self::PERMISSION]);
  }
};

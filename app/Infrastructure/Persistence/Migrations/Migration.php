<?php

namespace App\Infrastructure\Persistence\Migrations;

use App\Infrastructure\Persistence\Database;

/**
 * A reversible schema or data change.
 *
 * Migration files live in `database/migrations/` and must `return new class extends Migration { ... };`.
 * Each migration runs inside a transaction. Note that MySQL DDL (CREATE/ALTER/DROP) commits
 * implicitly, so only data statements (INSERT/UPDATE/DELETE) are really rolled back on failure:
 * keep schema migrations small and idempotent-friendly.
 */
abstract class Migration
{
  abstract public function up(Database $db): void;

  abstract public function down(Database $db): void;
}

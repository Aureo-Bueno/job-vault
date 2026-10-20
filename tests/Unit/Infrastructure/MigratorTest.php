<?php

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;
use App\Infrastructure\Persistence\Migrations\MigrationException;
use App\Infrastructure\Persistence\Migrations\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
  private const FIXTURES = __DIR__ . '/../../Fixtures';

  private Database $db;
  private string $tmpDir;

  protected function setUp(): void
  {
    $this->db = Database::fromPdo(new PDO('sqlite::memory:'));
    $this->tmpDir = sys_get_temp_dir() . '/migrator-test-' . bin2hex(random_bytes(4));
    mkdir($this->tmpDir);
  }

  protected function tearDown(): void
  {
    foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
      unlink($file);
    }
    rmdir($this->tmpDir);
  }

  public function testMigrateAppliesPendingMigrationsInOrderOnce(): void
  {
    $migrator = new Migrator($this->db, self::FIXTURES . '/migrations');

    $this->assertSame(
      ['2026_01_01_000001_create_teams', '2026_01_01_000002_seed_teams'],
      $migrator->migrate()
    );
    $this->assertSame(['alpha', 'beta'], $this->teams());
    $this->assertSame([], $migrator->migrate());
  }

  public function testStatusReportsBatchOrPending(): void
  {
    $this->copyFixture('2026_01_01_000001_create_teams.php');
    $migrator = new Migrator($this->db, $this->tmpDir);
    $migrator->migrate();
    $this->copyFixture('2026_01_01_000002_seed_teams.php');

    $this->assertSame([
      ['migration' => '2026_01_01_000001_create_teams', 'batch' => 1],
      ['migration' => '2026_01_01_000002_seed_teams', 'batch' => null],
    ], $migrator->status());
  }

  public function testRollbackRevertsOnlyTheLastBatch(): void
  {
    $this->copyFixture('2026_01_01_000001_create_teams.php');
    $migrator = new Migrator($this->db, $this->tmpDir);
    $migrator->migrate();
    $this->copyFixture('2026_01_01_000002_seed_teams.php');
    $migrator->migrate();

    $this->assertSame(['2026_01_01_000002_seed_teams'], $migrator->rollback());
    $this->assertSame([], $this->teams());
    $this->assertSame(1, $migrator->status()[0]['batch']);
  }

  public function testRollbackBySteps(): void
  {
    $migrator = new Migrator($this->db, self::FIXTURES . '/migrations');
    $migrator->migrate();

    $this->assertSame(
      ['2026_01_01_000002_seed_teams', '2026_01_01_000001_create_teams'],
      $migrator->rollback(2)
    );
    $this->assertFalse($this->tableExists('teams'));
  }

  public function testResetRevertsEverythingAndAllowsMigratingAgain(): void
  {
    $migrator = new Migrator($this->db, self::FIXTURES . '/migrations');
    $migrator->migrate();

    $this->assertCount(2, $migrator->reset());
    $this->assertFalse($this->tableExists('teams'));
    $this->assertSame(0, (int) $this->db->fetchValue('SELECT COUNT(*) FROM schema_migrations'));

    $migrator->migrate();
    $this->assertSame(['alpha', 'beta'], $this->teams());
  }

  public function testFailedMigrationIsRolledBackAndNotRecorded(): void
  {
    $this->db->exec('CREATE TABLE teams (name TEXT NOT NULL PRIMARY KEY)');
    $migrator = new Migrator($this->db, self::FIXTURES . '/broken-migrations');

    try {
      $migrator->migrate();
      $this->fail('Expected MigrationException');
    } catch (MigrationException $exception) {
      $this->assertStringContainsString('2026_01_01_000001_partial_seed', $exception->getMessage());
    }

    $this->assertSame([], $this->teams());
    $this->assertSame(null, $migrator->status()[0]['batch']);
    $this->assertFalse($this->db->connection()->inTransaction());
  }

  public function testFileThatDoesNotReturnAMigrationIsRejected(): void
  {
    $this->expectException(MigrationException::class);
    (new Migrator($this->db, self::FIXTURES . '/invalid-migrations'))->migrate();
  }

  public function testRollbackFailsWhenAppliedMigrationFileIsMissing(): void
  {
    $this->copyFixture('2026_01_01_000001_create_teams.php');
    $migrator = new Migrator($this->db, $this->tmpDir);
    $migrator->migrate();
    unlink($this->tmpDir . '/2026_01_01_000001_create_teams.php');

    $this->expectException(MigrationException::class);
    (new Migrator($this->db, $this->tmpDir))->rollback();
  }

  public function testCreateWritesALoadableStub(): void
  {
    $migrator = new Migrator($this->db, $this->tmpDir);

    $path = $migrator->create('Add Phone To Users');

    $this->assertMatchesRegularExpression('/\/\d{4}_\d{2}_\d{2}_\d{6}_add_phone_to_users\.php$/', $path);
    $this->assertInstanceOf(Migration::class, require $path);
    $this->assertSame(null, $migrator->status()[0]['batch']);
  }

  private function copyFixture(string $file): void
  {
    copy(self::FIXTURES . '/migrations/' . $file, $this->tmpDir . '/' . $file);
  }

  /**
   * @return list<string>
   */
  private function teams(): array
  {
    return array_column($this->db->fetchAll('SELECT name FROM teams ORDER BY name'), 'name');
  }

  private function tableExists(string $table): bool
  {
    return $this->db->fetchValue("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]) !== null;
  }
}

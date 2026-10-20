<?php

namespace App\Infrastructure\Persistence\Migrations;

use App\Infrastructure\Persistence\Database;
use Throwable;

/**
 * Discovers migration files, applies pending ones in batches and rolls them back.
 */
final class Migrator
{
  private const LOCK_TIMEOUT_SECONDS = 60;

  private MigrationRepository $repository;

  /** @var callable(string):void */
  private $output;

  /** @var array<string,Migration> */
  private array $loaded = [];

  /**
   * @param callable(string):void|null $output progress lines
   */
  public function __construct(private Database $db, private string $path, ?callable $output = null)
  {
    $this->repository = new MigrationRepository($db);
    $this->output = $output ?? static function (string $line): void {
    };
  }

  /**
   * Applies every pending migration in a new batch.
   *
   * @return list<string> applied migration names
   */
  public function migrate(): array
  {
    return $this->locked(function (): array {
      $applied = $this->repository->applied();
      $pending = array_values(array_diff(array_keys($this->files()), array_keys($applied)));

      if ($pending === []) {
        return [];
      }

      // Fail fast on invalid files before touching the database.
      array_map(fn (string $name): Migration => $this->load($name), $pending);

      $batch = $this->repository->nextBatch();
      foreach ($pending as $name) {
        $this->runMigration($name, 'up', fn () => $this->repository->log($name, $batch));
      }

      return $pending;
    });
  }

  /**
   * Reverts the last batch, or the last `$steps` migrations when `$steps > 0`.
   *
   * @return list<string> reverted migration names
   */
  public function rollback(int $steps = 0): array
  {
    return $this->locked(function () use ($steps): array {
      $applied = $this->repository->applied();
      if ($applied === []) {
        return [];
      }

      if ($steps > 0) {
        $targets = array_slice(array_reverse(array_keys($applied)), 0, $steps);
      } else {
        $lastBatch = max($applied);
        $targets = array_reverse(array_keys(array_filter($applied, static fn (int $b): bool => $b === $lastBatch)));
      }

      return $this->revert($targets);
    });
  }

  /**
   * Reverts every applied migration.
   *
   * @return list<string>
   */
  public function reset(): array
  {
    return $this->locked(fn (): array => $this->revert(array_reverse(array_keys($this->repository->applied()))));
  }

  /**
   * @return list<array{migration:string,batch:int|null}>
   */
  public function status(): array
  {
    $this->repository->ensureTable();
    $applied = $this->repository->applied();
    $names = array_unique(array_merge(array_keys($applied), array_keys($this->files())));
    sort($names);

    return array_map(
      static fn (string $name): array => ['migration' => $name, 'batch' => $applied[$name] ?? null],
      $names
    );
  }

  /**
   * Writes a new empty migration file and returns its path.
   */
  public function create(string $description): string
  {
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($description)), '_');
    if ($slug === '') {
      throw new MigrationException('Migration name must contain letters or digits.');
    }

    if (!is_dir($this->path) && !mkdir($this->path, 0775, true) && !is_dir($this->path)) {
      throw new MigrationException(sprintf('Cannot create migrations directory "%s".', $this->path));
    }

    $file = sprintf('%s/%s_%s.php', rtrim($this->path, '/'), date('Y_m_d_His'), $slug);
    if (file_exists($file)) {
      throw new MigrationException(sprintf('Migration "%s" already exists.', basename($file)));
    }

    file_put_contents($file, self::STUB);
    return $file;
  }

  /**
   * @param list<string> $names newest first
   * @return list<string>
   */
  private function revert(array $names): array
  {
    $files = $this->files();
    foreach ($names as $name) {
      if (!isset($files[$name])) {
        throw new MigrationException(sprintf('Migration "%s" is recorded as applied but its file is missing.', $name));
      }
    }

    foreach ($names as $name) {
      $this->runMigration($name, 'down', fn () => $this->repository->forget($name));
    }

    return $names;
  }

  /**
   * @param 'up'|'down' $direction
   * @param callable():void $record
   */
  private function runMigration(string $name, string $direction, callable $record): void
  {
    $migration = $this->load($name);
    ($this->output)(sprintf('%s %s ...', $direction === 'up' ? 'Migrating' : 'Rolling back', $name));
    $startedAt = microtime(true);

    $this->db->beginTransaction();
    try {
      $migration->{$direction}($this->db);
      $record();
      $this->db->commit();
    } catch (Throwable $exception) {
      $this->db->rollBack();
      $cause = $exception->getPrevious() ?? $exception;
      throw new MigrationException(
        sprintf('Migration "%s" (%s) failed: %s', $name, $direction, $cause->getMessage()),
        0,
        $exception
      );
    }

    ($this->output)(sprintf('  done in %.1f ms', (microtime(true) - $startedAt) * 1000));
  }

  /**
   * @template T
   * @param callable():T $callback
   * @return T
   */
  private function locked(callable $callback): mixed
  {
    $this->repository->ensureTable();

    // Serialises concurrent runners (e.g. two containers starting together). MySQL only.
    $useLock = $this->db->driverName() === 'mysql';
    if ($useLock) {
      $acquired = (int) $this->db->fetchValue(
        "SELECT GET_LOCK(CONCAT(DATABASE(), ':migrations'), ?)",
        [self::LOCK_TIMEOUT_SECONDS]
      );
      if ($acquired !== 1) {
        throw new MigrationException('Could not acquire the migrations lock (another runner is active?).');
      }
    }

    try {
      return $callback();
    } finally {
      if ($useLock) {
        $this->db->fetchValue("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':migrations'))");
      }
    }
  }

  /**
   * @return array<string,string> migration name => file path, sorted by name
   */
  private function files(): array
  {
    $files = [];
    foreach (glob(rtrim($this->path, '/') . '/*.php') ?: [] as $file) {
      $files[basename($file, '.php')] = $file;
    }

    ksort($files, SORT_STRING);
    return $files;
  }

  private function load(string $name): Migration
  {
    if (isset($this->loaded[$name])) {
      return $this->loaded[$name];
    }

    $file = $this->files()[$name] ?? null;
    if ($file === null) {
      throw new MigrationException(sprintf('Migration file for "%s" not found.', $name));
    }

    $migration = require $file;
    if (!$migration instanceof Migration) {
      throw new MigrationException(sprintf('"%s" must return an instance of %s.', basename($file), Migration::class));
    }

    return $this->loaded[$name] = $migration;
  }

  private const STUB = <<<'PHP'
<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

return new class extends Migration {
  public function up(Database $db): void
  {
  }

  public function down(Database $db): void
  {
  }
};

PHP;
}

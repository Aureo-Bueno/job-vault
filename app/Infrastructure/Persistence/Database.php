<?php

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Persistence\Exceptions\DatabaseConnectionException;
use App\Infrastructure\Persistence\Exceptions\DatabaseQueryException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Thin PDO wrapper: lazy connection, prepared statements and exception translation.
 */
final class Database
{
  private ?PDO $pdo;

  /**
   * Open transaction levels: null = real transaction, string = SAVEPOINT name.
   *
   * @var list<string|null>
   */
  private array $transactions = [];

  /**
   * @param array{host:string,port:string,name:string,user:string,password:string}|null $config
   */
  private function __construct(private ?array $config, ?PDO $pdo = null)
  {
    $this->pdo = $pdo;
  }

  public static function fromEnvironment(): self
  {
    return new self([
      'host' => self::env('DB_HOST', 'mysql'),
      'port' => self::env('DB_PORT', '3306'),
      'name' => self::env('DB_NAME', 'myapp_db'),
      'user' => self::env('DB_USER', 'appuser'),
      'password' => self::env('DB_PASSWORD', 'app_password'),
    ]);
  }

  public static function fromPdo(PDO $pdo): self
  {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return new self(null, $pdo);
  }

  public function connection(): PDO
  {
    if ($this->pdo instanceof PDO) {
      return $this->pdo;
    }

    $config = (array) $this->config;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']);

    try {
      $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $exception) {
      throw new DatabaseConnectionException('Database connection failed.', 0, $exception);
    }

    return $this->pdo;
  }

  /**
   * @param array<int|string,mixed> $params
   */
  public function run(string $sql, array $params = []): PDOStatement
  {
    $connection = $this->connection();

    try {
      $statement = $connection->prepare($sql);
      $statement->execute($params);
      return $statement;
    } catch (PDOException $exception) {
      throw new DatabaseQueryException('Database query failed.', 0, $exception);
    }
  }

  /**
   * @param array<int|string,mixed> $params
   * @return array<int,array<string,mixed>>
   */
  public function fetchAll(string $sql, array $params = []): array
  {
    return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * @param array<int|string,mixed> $params
   * @return array<string,mixed>|null
   */
  public function fetchOne(string $sql, array $params = []): ?array
  {
    $row = $this->run($sql, $params)->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
  }

  /**
   * @param array<int|string,mixed> $params
   */
  public function fetchValue(string $sql, array $params = []): mixed
  {
    $value = $this->run($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
  }

  /**
   * Executes raw SQL without parameters (DDL, SAVEPOINT, multi-statement scripts).
   */
  public function exec(string $sql): void
  {
    $connection = $this->connection();

    try {
      $connection->exec($sql);
    } catch (PDOException $exception) {
      throw new DatabaseQueryException('Database query failed.', 0, $exception);
    }
  }

  public function driverName(): string
  {
    return (string) $this->connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
  }

  /**
   * Starts a transaction, or a SAVEPOINT when one is already open, so nested
   * begin/commit pairs (e.g. a TransactionBehavior inside a test transaction)
   * never commit the outer unit of work.
   */
  public function beginTransaction(): void
  {
    $connection = $this->connection();

    if (!$connection->inTransaction()) {
      $connection->beginTransaction();
      $this->transactions[] = null;
      return;
    }

    $savepoint = 'sp_' . (count($this->transactions) + 1);
    $this->exec('SAVEPOINT ' . $savepoint);
    $this->transactions[] = $savepoint;
  }

  public function commit(): void
  {
    if ($this->transactions === []) {
      return;
    }

    $savepoint = array_pop($this->transactions);
    // MySQL DDL commits implicitly, so the transaction may already be gone.
    if (!$this->pdo?->inTransaction()) {
      return;
    }

    if ($savepoint === null) {
      $this->pdo->commit();
      return;
    }

    $this->exec('RELEASE SAVEPOINT ' . $savepoint);
  }

  public function rollBack(): void
  {
    if ($this->transactions === []) {
      return;
    }

    $savepoint = array_pop($this->transactions);
    if (!$this->pdo?->inTransaction()) {
      return;
    }

    if ($savepoint === null) {
      $this->pdo->rollBack();
      return;
    }

    $this->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
  }

  private static function env(string $key, string $default): string
  {
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
  }
}

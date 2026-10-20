<?php

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Exceptions\DatabaseQueryException;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseTransactionTest extends TestCase
{
  private Database $db;

  protected function setUp(): void
  {
    $this->db = Database::fromPdo(new PDO('sqlite::memory:'));
    $this->db->exec('CREATE TABLE items (name TEXT NOT NULL)');
  }

  public function testInnerCommitDoesNotCommitOuterTransaction(): void
  {
    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['outer']);

    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['inner']);
    $this->db->commit();

    $this->assertTrue($this->db->connection()->inTransaction());

    $this->db->rollBack();

    $this->assertSame(0, (int) $this->db->fetchValue('SELECT COUNT(*) FROM items'));
  }

  public function testInnerRollBackOnlyUndoesInnerWork(): void
  {
    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['outer']);

    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['inner']);
    $this->db->rollBack();

    $this->db->commit();

    $this->assertSame(['outer'], array_column($this->db->fetchAll('SELECT name FROM items'), 'name'));
    $this->assertFalse($this->db->connection()->inTransaction());
  }

  public function testCommitAndRollBackWithoutTransactionAreNoOps(): void
  {
    $this->db->commit();
    $this->db->rollBack();

    $this->db->run('INSERT INTO items (name) VALUES (?)', ['autocommit']);
    $this->assertSame(1, (int) $this->db->fetchValue('SELECT COUNT(*) FROM items'));
  }

  public function testTransactionCanBeReusedAfterCompletion(): void
  {
    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['first']);
    $this->db->commit();

    $this->db->beginTransaction();
    $this->db->run('INSERT INTO items (name) VALUES (?)', ['second']);
    $this->db->rollBack();

    $this->assertSame(['first'], array_column($this->db->fetchAll('SELECT name FROM items'), 'name'));
  }

  public function testExecTranslatesErrors(): void
  {
    $this->expectException(DatabaseQueryException::class);
    $this->db->exec('CREATE TABLE items (name TEXT)');
  }
}

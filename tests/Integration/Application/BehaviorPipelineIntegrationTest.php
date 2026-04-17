<?php

namespace Tests\Integration\Application;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Abstractions\SimpleCommandBus;
use App\Application\Behaviors\AuthorizationBehavior;
use App\Application\Behaviors\TransactionBehavior;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BehaviorPipelineIntegrationTest extends TestCase
{
  public function testAuthorizationBehaviorBlocksCommandExecution(): void
  {
    $state = (object) ['executed' => false];
    $handler = new ProbeCommandHandler($state);

    $bus = new SimpleCommandBus(
      [$handler],
      [
        new AuthorizationBehavior(function (object $message): void {
          throw new RuntimeException('Forbidden by authorization behavior.');
        }),
      ]
    );

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Forbidden by authorization behavior.');

    try {
      $bus->dispatch(new ProbeCommand('blocked'));
    } finally {
      $this->assertFalse($state->executed);
    }
  }

  public function testTransactionBehaviorRollsBackWhenHandlerFails(): void
  {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');

    $handler = new FailingInsertCommandHandler($pdo);

    $bus = new SimpleCommandBus(
      [$handler],
      [
        new TransactionBehavior(
          function () use ($pdo): void {
            $pdo->beginTransaction();
          },
          function () use ($pdo): void {
            $pdo->commit();
          },
          function () use ($pdo): void {
            if ($pdo->inTransaction()) {
              $pdo->rollBack();
            }
          }
        ),
      ]
    );

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Simulated failure after insert.');

    try {
      $bus->dispatch(new ProbeCommand('rollback-case'));
    } finally {
      $count = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
      $this->assertSame(0, $count, 'Row must be rolled back on failure.');
      $this->assertFalse($pdo->inTransaction(), 'Transaction must be closed after rollback.');
    }
  }
}

final class ProbeCommand implements Command
{
  public function __construct(public string $name)
  {
  }
}

final class ProbeCommandHandler implements CommandHandlerInterface
{
  public function __construct(private object $state)
  {
  }

  public function commandClass(): string
  {
    return ProbeCommand::class;
  }

  public function handle(Command $command): bool
  {
    $this->state->executed = true;
    return true;
  }
}

final class FailingInsertCommandHandler implements CommandHandlerInterface
{
  public function __construct(private PDO $pdo)
  {
  }

  public function commandClass(): string
  {
    return ProbeCommand::class;
  }

  public function handle(Command $command): bool
  {
    $stmt = $this->pdo->prepare('INSERT INTO items(name) VALUES(:name)');
    $stmt->execute(['name' => ($command instanceof ProbeCommand ? $command->name : 'unknown')]);

    throw new RuntimeException('Simulated failure after insert.');
  }
}

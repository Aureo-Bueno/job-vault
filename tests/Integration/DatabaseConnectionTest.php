<?php

namespace Tests\Integration;

use App\Infrastructure\Persistence\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseConnectionTest extends TestCase
{
  public function testDatabaseConnection(): void
  {
    if (getenv('RUN_INTEGRATION') !== '1') {
      $this->markTestSkipped('Integration tests disabled. Set RUN_INTEGRATION=1 to enable.');
    }

    $this->assertSame(1, (int) Database::fromEnvironment()->fetchValue('SELECT 1'));
  }
}

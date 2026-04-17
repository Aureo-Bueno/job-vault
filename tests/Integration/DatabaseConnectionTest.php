<?php

namespace Tests\Integration;

use App\Db\Database;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
  public function testDatabaseConnection(): void
  {
    if (getenv('RUN_INTEGRATION') !== '1') {
      $this->markTestSkipped('Integration tests disabled. Set RUN_INTEGRATION=1 to enable.');
    }

    $db = new Database('users');
    $result = $db->select(null, null, '1');

    $this->assertNotFalse($result);
  }
}

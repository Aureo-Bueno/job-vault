<?php

namespace Tests\Support;

use App\Application\Ports\LoggerInterface;

final class NullLogger implements LoggerInterface
{
  /** @var array<int,array{0:string,1:string,2:array<string,mixed>}> */
  public array $records = [];

  public function info(string $message, array $context = []): void
  {
    $this->records[] = ['info', $message, $context];
  }

  public function warning(string $message, array $context = []): void
  {
    $this->records[] = ['warning', $message, $context];
  }

  public function error(string $message, array $context = []): void
  {
    $this->records[] = ['error', $message, $context];
  }
}

<?php

namespace Tests\Support;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandBusInterface;

class FakeCommandBus implements CommandBusInterface
{
  /** @var Command[] */
  public array $messages = [];

  /**
   * @var callable(Command):mixed
   */
  private $resolver;

  /**
   * @param callable(Command):mixed|null $resolver
   */
  public function __construct(?callable $resolver = null)
  {
    $this->resolver = $resolver ?? static fn (Command $command): mixed => true;
  }

  public function dispatch(Command $command): mixed
  {
    $this->messages[] = $command;
    return ($this->resolver)($command);
  }
}

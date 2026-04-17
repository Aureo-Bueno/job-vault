<?php

namespace Tests\Support;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryBusInterface;

class FakeQueryBus implements QueryBusInterface
{
  /** @var Query[] */
  public array $messages = [];

  /**
   * @var callable(Query):mixed
   */
  private $resolver;

  /**
   * @param callable(Query):mixed|null $resolver
   */
  public function __construct(?callable $resolver = null)
  {
    $this->resolver = $resolver ?? static fn (Query $query): mixed => null;
  }

  public function ask(Query $query): mixed
  {
    $this->messages[] = $query;
    return ($this->resolver)($query);
  }
}

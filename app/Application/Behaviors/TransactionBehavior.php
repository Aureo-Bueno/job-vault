<?php

namespace App\Application\Behaviors;

/**
 * Wraps message execution in begin/commit/rollback callbacks.
 */
final class TransactionBehavior implements BehaviorInterface
{
  /** @var callable():void */
  private $begin;

  /** @var callable():void */
  private $commit;

  /** @var callable():void */
  private $rollback;

  /**
   * @param callable():void $begin
   * @param callable():void $commit
   * @param callable():void $rollback
   */
  public function __construct(callable $begin, callable $commit, callable $rollback)
  {
    $this->begin = $begin;
    $this->commit = $commit;
    $this->rollback = $rollback;
  }

  public function handle(object $message, callable $next): mixed
  {
    ($this->begin)();

    try {
      $result = $next($message);
      ($this->commit)();
      return $result;
    } catch (\Throwable $exception) {
      ($this->rollback)();
      throw $exception;
    }
  }
}

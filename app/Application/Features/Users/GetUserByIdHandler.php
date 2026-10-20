<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\UserDto;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class GetUserByIdHandler implements QueryHandlerInterface
{
  public function __construct(private UserRepositoryInterface $users)
  {
  }

  public function queryClass(): string
  {
    return GetUserByIdQuery::class;
  }

  public function handle(Query $query): ?UserDto
  {
    if (!$query instanceof GetUserByIdQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    if (!UserId::isValid($query->userId)) {
      return null;
    }

    $user = $this->users->get(UserId::fromString($query->userId));
    return $user ? UserDto::fromUser($user) : null;
  }
}

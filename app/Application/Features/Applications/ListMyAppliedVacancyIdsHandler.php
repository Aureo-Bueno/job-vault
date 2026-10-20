<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Queries\Applications\ListMyAppliedVacancyIdsQuery;
use App\Application\Security\CurrentUserProvider;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use InvalidArgumentException;

final class ListMyAppliedVacancyIdsHandler implements QueryHandlerInterface
{
  public function __construct(
    private JobApplicationRepositoryInterface $applications,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function queryClass(): string
  {
    return ListMyAppliedVacancyIdsQuery::class;
  }

  /** @return string[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListMyAppliedVacancyIdsQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $current = $this->currentUser->currentUser();
    if ($current === null || !UserId::isValid($current->id)) {
      return [];
    }

    return $this->applications->vacancyIdsAppliedBy(UserId::fromString($current->id));
  }
}

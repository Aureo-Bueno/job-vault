<?php

namespace App\Application\Features\Profile;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\CandidateProfileDto;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Queries\Profile\GetMyCandidateProfileQuery;
use App\Application\Security\CurrentUserProvider;
use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\User\UserId;
use InvalidArgumentException;

final class GetMyCandidateProfileHandler implements QueryHandlerInterface
{
  public function __construct(
    private CandidateProfileRepositoryInterface $profiles,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function queryClass(): string
  {
    return GetMyCandidateProfileQuery::class;
  }

  public function handle(Query $query): CandidateProfileDto
  {
    if (!$query instanceof GetMyCandidateProfileQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $logged = $this->currentUser->currentUser();
    if ($logged === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $userId = UserId::fromString($logged->id);
    $profile = $this->profiles->getByUserId($userId) ?? CandidateProfile::emptyFor($userId);

    return CandidateProfileDto::fromProfile($profile);
  }
}

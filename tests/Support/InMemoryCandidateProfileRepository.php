<?php

namespace Tests\Support;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\User\UserId;

final class InMemoryCandidateProfileRepository implements CandidateProfileRepositoryInterface
{
  /** @var array<string,CandidateProfile> */
  private array $profiles = [];

  public function getByUserId(UserId $userId): ?CandidateProfile
  {
    return isset($this->profiles[$userId->value()]) ? clone $this->profiles[$userId->value()] : null;
  }

  public function getByUserIds(array $userIds): array
  {
    $result = [];
    foreach ($userIds as $id) {
      $key = $id->value();
      if (isset($this->profiles[$key])) {
        $result[$key] = clone $this->profiles[$key];
      }
    }

    return $result;
  }

  public function save(CandidateProfile $profile): void
  {
    $this->profiles[$profile->userId()->value()] = clone $profile;
  }
}

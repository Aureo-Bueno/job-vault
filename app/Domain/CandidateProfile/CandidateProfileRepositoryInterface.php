<?php

namespace App\Domain\CandidateProfile;

use App\Domain\User\UserId;

interface CandidateProfileRepositoryInterface
{
  public function getByUserId(UserId $userId): ?CandidateProfile;

  /**
   * @param list<UserId> $userIds
   * @return array<string,CandidateProfile> indexed by user_id string
   */
  public function getByUserIds(array $userIds): array;

  public function save(CandidateProfile $profile): void;
}

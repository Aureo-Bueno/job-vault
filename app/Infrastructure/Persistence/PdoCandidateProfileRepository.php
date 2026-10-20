<?php

namespace App\Infrastructure\Persistence;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;

final class PdoCandidateProfileRepository implements CandidateProfileRepositoryInterface
{
  private const COLUMNS = 'user_id, headline, bio, area, seniority, work_mode, linkedin_url, github_url, updated_at';

  public function __construct(private Database $db)
  {
  }

  public function getByUserId(UserId $userId): ?CandidateProfile
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM candidate_profiles WHERE user_id = ?', [$userId->value()]);
    if ($row === null) {
      return null;
    }

    $topicsByUser = $this->loadTopics([$userId->value()]);
    return $this->map($row, $topicsByUser[$userId->value()] ?? []);
  }

  public function getByUserIds(array $userIds): array
  {
    if ($userIds === []) {
      return [];
    }

    $ids = array_values(array_unique(array_map(static fn (UserId $id): string => $id->value(), $userIds)));
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM candidate_profiles WHERE user_id IN (' . $placeholders . ')',
      $ids
    );

    $topicsByUser = $this->loadTopics($ids);
    $result = [];
    foreach ($rows as $row) {
      $uid = (string) $row['user_id'];
      $result[$uid] = $this->map($row, $topicsByUser[$uid] ?? []);
    }

    return $result;
  }

  public function save(CandidateProfile $profile): void
  {
    $userId = $profile->userId()->value();
    $values = [
      $profile->headline(),
      $profile->bio() !== '' ? $profile->bio() : null,
      $profile->area()->value,
      $profile->seniority()->value,
      $profile->workMode()->value,
      $profile->linkedinUrl() !== '' ? $profile->linkedinUrl() : null,
      $profile->githubUrl() !== '' ? $profile->githubUrl() : null,
      Sql::fromDate($profile->updatedAt()),
      $userId,
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM candidate_profiles WHERE user_id = ?', [$userId]);
    $this->db->run(
      $exists
        ? 'UPDATE candidate_profiles SET headline = ?, bio = ?, area = ?, seniority = ?, work_mode = ?, linkedin_url = ?, github_url = ?, updated_at = ? WHERE user_id = ?'
        : 'INSERT INTO candidate_profiles (headline, bio, area, seniority, work_mode, linkedin_url, github_url, updated_at, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
      $values
    );

    $this->db->run('DELETE FROM candidate_topics WHERE user_id = ?', [$userId]);
    foreach ($profile->topics() as $topic) {
      $this->db->run(
        'INSERT INTO candidate_topics (user_id, slug, label) VALUES (?, ?, ?)',
        [$userId, $topic->slug(), $topic->label()]
      );
    }
  }

  /**
   * @param list<string> $userIds
   * @return array<string,list<TopicTag>>
   */
  private function loadTopics(array $userIds): array
  {
    if ($userIds === []) {
      return [];
    }

    $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
    $rows = $this->db->fetchAll(
      'SELECT user_id, slug, label FROM candidate_topics WHERE user_id IN (' . $placeholders . ') ORDER BY label ASC',
      $userIds
    );

    $grouped = [];
    foreach ($rows as $row) {
      $uid = (string) $row['user_id'];
      $grouped[$uid][] = TopicTag::reconstitute((string) $row['slug'], (string) $row['label']);
    }

    return $grouped;
  }

  /**
   * @param array<string,mixed> $row
   * @param list<TopicTag> $topics
   */
  private function map(array $row, array $topics): CandidateProfile
  {
    return CandidateProfile::reconstitute(
      UserId::fromString((string) $row['user_id']),
      (string) ($row['headline'] ?? ''),
      (string) ($row['bio'] ?? ''),
      WorkArea::fromStored(isset($row['area']) ? (string) $row['area'] : null),
      Seniority::fromStored(isset($row['seniority']) ? (string) $row['seniority'] : null),
      WorkMode::fromStored(isset($row['work_mode']) ? (string) $row['work_mode'] : null),
      (string) ($row['linkedin_url'] ?? ''),
      (string) ($row['github_url'] ?? ''),
      $topics,
      Sql::toDate($row['updated_at'] ?? null)
    );
  }
}

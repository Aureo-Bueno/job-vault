<?php

namespace App\Infrastructure\Persistence;

use App\Db\Database;
use App\Domain\Repository\ApplicationRepositoryInterface;
use App\Util\Logger;
use PDO;

class PdoApplicationRepository implements ApplicationRepositoryInterface
{
  private Database $db;
  private Logger $logger;

  public function __construct()
  {
    $this->db = new Database('applications');
    $this->logger = new Logger('application');
  }

  public function create(string $userId, string $vacancyId): bool
  {
    try {
      $this->db->insert([
        'user_id' => $userId,
        'vacancy_id' => $vacancyId
      ]);

      $this->logger->info('Application created', [
        'user_id' => $userId,
        'vacancy_id' => $vacancyId
      ]);

      return true;
    } catch (\PDOException $e) {
      $this->logger->error('Failed to create application', [
        'error' => $e->getMessage(),
        'user_id' => $userId,
        'vacancy_id' => $vacancyId
      ]);
      return false;
    }
  }

  public function hasApplied(string $userId, string $vacancyId): bool
  {
    try {
      $stmt = $this->db->execute(
        'SELECT 1 FROM applications WHERE user_id = ? AND vacancy_id = ? LIMIT 1',
        [$userId, $vacancyId]
      );

      return (bool) $stmt->fetchColumn();
    } catch (\PDOException $e) {
      $this->logger->error('Failed to check application', [
        'error' => $e->getMessage(),
        'user_id' => $userId,
        'vacancy_id' => $vacancyId
      ]);
      return false;
    }
  }

  public function getAppliedVacancyIdsByUser(string $userId): array
  {
    try {
      $stmt = $this->db->execute(
        'SELECT vacancy_id FROM applications WHERE user_id = ?',
        [$userId]
      );
      $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

      return array_map('strval', $rows ?: []);
    } catch (\PDOException $e) {
      $this->logger->error('Failed to fetch user applications', [
        'error' => $e->getMessage(),
        'user_id' => $userId
      ]);
      return [];
    }
  }
}

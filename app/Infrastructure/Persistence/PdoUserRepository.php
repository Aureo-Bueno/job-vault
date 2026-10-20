<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Role\RoleId;
use App\Domain\Shared\PageRequest;
use App\Domain\User\EmailAddress;
use App\Domain\User\PasswordHash;
use App\Domain\User\PersonName;
use App\Domain\User\User;
use App\Domain\User\UserFilter;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;

final class PdoUserRepository implements UserRepositoryInterface
{
  private const COLUMNS = 'id, name, email, password, role_id';

  public function __construct(private Database $db)
  {
  }

  public function get(UserId $id): ?User
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ?', [$id->value()]);
    return $row ? $this->map($row) : null;
  }

  public function findByEmail(EmailAddress $email): ?User
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE email = ? LIMIT 1', [$email->value()]);
    return $row ? $this->map($row) : null;
  }

  public function matching(UserFilter $filter, PageRequest $page): array
  {
    [$where, $params] = $this->criteria($filter);
    $rows = $this->db->fetchAll(
      'SELECT ' . self::COLUMNS . ' FROM users' . $where . ' ORDER BY name ASC, id ASC' . Sql::limit($page),
      $params
    );

    return array_map([$this, 'map'], $rows);
  }

  public function countMatching(UserFilter $filter): int
  {
    [$where, $params] = $this->criteria($filter);
    return (int) $this->db->fetchValue('SELECT COUNT(*) FROM users' . $where, $params);
  }

  public function countPerRole(): array
  {
    $rows = $this->db->fetchAll(
      'SELECT role_id, COUNT(*) AS total FROM users WHERE role_id IS NOT NULL GROUP BY role_id'
    );

    $counts = [];
    foreach ($rows as $row) {
      $counts[(string) $row['role_id']] = (int) $row['total'];
    }

    return $counts;
  }

  public function save(User $user): void
  {
    $values = [
      $user->name()->value(),
      $user->email()->value(),
      $user->passwordHash()->value(),
      $user->roleId()?->value(),
      $user->id()->value(),
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM users WHERE id = ?', [$user->id()->value()]);
    $this->db->run(
      $exists
        ? 'UPDATE users SET name = ?, email = ?, password = ?, role_id = ? WHERE id = ?'
        : 'INSERT INTO users (name, email, password, role_id, id) VALUES (?, ?, ?, ?, ?)',
      $values
    );
  }

  public function remove(UserId $id): void
  {
    $this->db->run('DELETE FROM users WHERE id = ?', [$id->value()]);
  }

  /**
   * @return array{0:string,1:array<string,string>}
   */
  private function criteria(UserFilter $filter): array
  {
    $conditions = [];
    $params = [];

    if ($filter->term !== null) {
      $conditions[] = '(name LIKE :term_name OR email LIKE :term_email)';
      $params['term_name'] = Sql::likePattern($filter->term);
      $params['term_email'] = $params['term_name'];
    }

    if ($filter->roleId !== null) {
      $conditions[] = 'role_id = :role_id';
      $params['role_id'] = $filter->roleId->value();
    }

    return [Sql::where($conditions), $params];
  }

  /**
   * @param array<string,mixed> $row
   */
  private function map(array $row): User
  {
    return User::reconstitute(
      UserId::fromString((string) $row['id']),
      PersonName::fromString((string) $row['name']),
      EmailAddress::fromString((string) $row['email']),
      PasswordHash::fromStored((string) $row['password']),
      isset($row['role_id']) && $row['role_id'] !== '' ? RoleId::fromString((string) $row['role_id']) : null
    );
  }
}

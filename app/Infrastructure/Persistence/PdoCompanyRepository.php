<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Company\CompanyRepositoryInterface;

final class PdoCompanyRepository implements CompanyRepositoryInterface
{
  private const COLUMNS = 'id, name, sector, website, description, created_at';

  public function __construct(private Database $db)
  {
  }

  public function get(CompanyId $id): ?Company
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM companies WHERE id = ?', [$id->value()]);
    return $row ? $this->map($row) : null;
  }

  public function findByName(CompanyName $name): ?Company
  {
    $row = $this->db->fetchOne('SELECT ' . self::COLUMNS . ' FROM companies WHERE name = ? LIMIT 1', [$name->value()]);
    return $row ? $this->map($row) : null;
  }

  public function all(): array
  {
    $rows = $this->db->fetchAll('SELECT ' . self::COLUMNS . ' FROM companies ORDER BY name ASC, id ASC');
    return array_map([$this, 'map'], $rows);
  }

  public function save(Company $company): void
  {
    $values = [
      $company->name()->value(),
      $company->sector(),
      $company->website() !== '' ? $company->website() : null,
      $company->description() !== '' ? $company->description() : null,
      Sql::fromDate($company->createdAt()),
      $company->id()->value(),
    ];

    $exists = $this->db->fetchValue('SELECT 1 FROM companies WHERE id = ?', [$company->id()->value()]);
    $this->db->run(
      $exists
        ? 'UPDATE companies SET name = ?, sector = ?, website = ?, description = ?, created_at = ? WHERE id = ?'
        : 'INSERT INTO companies (name, sector, website, description, created_at, id) VALUES (?, ?, ?, ?, ?, ?)',
      $values
    );
  }

  public function remove(CompanyId $id): void
  {
    $this->db->run('DELETE FROM companies WHERE id = ?', [$id->value()]);
  }

  /**
   * @param array<string,mixed> $row
   */
  private function map(array $row): Company
  {
    return Company::reconstitute(
      CompanyId::fromString((string) $row['id']),
      CompanyName::fromString((string) $row['name']),
      (string) ($row['sector'] ?? 'Tecnologia'),
      (string) ($row['website'] ?? ''),
      (string) ($row['description'] ?? ''),
      Sql::toDate($row['created_at'] ?? null)
    );
  }
}

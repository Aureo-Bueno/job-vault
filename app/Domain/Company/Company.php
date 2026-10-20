<?php

namespace App\Domain\Company;

use App\Domain\Shared\Guard;
use DateTimeImmutable;

/**
 * Company aggregate: owns corporate identity; 1 company can have N recruiter users and N vacancies.
 */
final class Company
{
  private const MAX_SECTOR_LENGTH = 100;
  private const MAX_WEBSITE_LENGTH = 255;
  private const MAX_DESCRIPTION_LENGTH = 2000;

  private function __construct(
    private CompanyId $id,
    private CompanyName $name,
    private string $sector,
    private string $website,
    private string $description,
    private DateTimeImmutable $createdAt
  ) {
  }

  public static function register(
    CompanyId $id,
    CompanyName $name,
    string $sector = 'Tecnologia',
    string $website = '',
    string $description = '',
    ?DateTimeImmutable $now = null
  ): self {
    return new self(
      $id,
      $name,
      self::normalizeSector($sector),
      self::normalizeWebsite($website),
      self::normalizeDescription($description),
      $now ?? new DateTimeImmutable()
    );
  }

  public static function reconstitute(
    CompanyId $id,
    CompanyName $name,
    string $sector,
    string $website,
    string $description,
    DateTimeImmutable $createdAt
  ): self {
    return new self($id, $name, trim($sector) !== '' ? trim($sector) : 'Tecnologia', trim($website), trim($description), $createdAt);
  }

  public function updateDetails(
    CompanyName $name,
    string $sector,
    string $website = '',
    string $description = ''
  ): void {
    $this->name = $name;
    $this->sector = self::normalizeSector($sector);
    $this->website = self::normalizeWebsite($website);
    $this->description = self::normalizeDescription($description);
  }

  public function id(): CompanyId
  {
    return $this->id;
  }

  public function name(): CompanyName
  {
    return $this->name;
  }

  public function sector(): string
  {
    return $this->sector;
  }

  public function website(): string
  {
    return $this->website;
  }

  public function description(): string
  {
    return $this->description;
  }

  public function createdAt(): DateTimeImmutable
  {
    return $this->createdAt;
  }

  private static function normalizeSector(string $sector): string
  {
    $normalized = Guard::optionalText(
      $sector,
      self::MAX_SECTOR_LENGTH,
      'O setor da empresa deve ter no máximo ' . self::MAX_SECTOR_LENGTH . ' caracteres.'
    );

    return $normalized !== '' ? $normalized : 'Tecnologia';
  }

  private static function normalizeWebsite(string $website): string
  {
    return Guard::optionalText(
      $website,
      self::MAX_WEBSITE_LENGTH,
      'O site da empresa deve ter no máximo ' . self::MAX_WEBSITE_LENGTH . ' caracteres.'
    );
  }

  private static function normalizeDescription(string $description): string
  {
    return Guard::optionalText(
      $description,
      self::MAX_DESCRIPTION_LENGTH,
      'A descrição da empresa deve ter no máximo ' . self::MAX_DESCRIPTION_LENGTH . ' caracteres.'
    );
  }
}

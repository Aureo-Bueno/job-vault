<?php

namespace App\Domain\Entity;

use App\Domain\Model\Vacancy;

final class VacancyPosting
{
  private ?string $id;
  private string $title;
  private string $description;
  private string $status;
  private string $createdAt;

  private function __construct(
    ?string $id,
    string $title,
    string $description,
    string $status,
    string $createdAt
  ) {
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->status = $status;
    $this->createdAt = $createdAt;
  }

  public static function fromModel(Vacancy $vacancy): self
  {
    $title = self::normalizeTitle($vacancy->title);
    $description = self::normalizeDescription($vacancy->description);
    $status = self::normalizeStatus($vacancy->isActive);
    $createdAt = self::normalizeCreatedAt($vacancy->createdAt);

    return new self(
      $vacancy->id,
      $title,
      $description,
      $status,
      $createdAt
    );
  }

  public function toModel(): Vacancy
  {
    return new Vacancy(
      $this->id,
      $this->title,
      $this->description,
      $this->status,
      $this->createdAt
    );
  }

  private static function normalizeTitle(string $value): string
  {
    $normalizedValue = trim($value);
    if ($normalizedValue !== '') {
      return $normalizedValue;
    }

    return 'Sem título';
  }

  private static function normalizeDescription(string $value): string
  {
    $normalizedValue = trim($value);
    if ($normalizedValue !== '') {
      return $normalizedValue;
    }

    return 'Sem descrição';
  }

  private static function normalizeStatus(string $value): string
  {
    if (in_array($value, ['s', 'n'], true)) {
      return $value;
    }

    return 'n';
  }

  private static function normalizeCreatedAt(string $value): string
  {
    $normalizedValue = trim($value);
    if ($normalizedValue !== '') {
      return $normalizedValue;
    }

    return date('Y-m-d H:i:s');
  }
}

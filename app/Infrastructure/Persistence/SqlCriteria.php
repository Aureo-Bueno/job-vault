<?php

namespace App\Infrastructure\Persistence;

use App\Domain\ValueObject\SearchTerm;

final class SqlCriteria
{
  private array $conditions = [];
  private array $parameters = [];

  public function addContainsAny(array $fields, SearchTerm $searchTerm, string $parameterPrefix): void
  {
    $pattern = $searchTerm->toSqlLikePattern();
    $fieldConditions = [];

    foreach (array_values($fields) as $index => $field) {
      $parameterName = $parameterPrefix . '_' . $index;
      $fieldConditions[] = $field . ' LIKE :' . $parameterName;
      $this->parameters[$parameterName] = $pattern;
    }

    $this->conditions[] = '(' . implode(' OR ', $fieldConditions) . ')';
  }

  public function addEquals(string $field, string $parameterName, string $value): void
  {
    $this->conditions[] = $field . ' = :' . $parameterName;
    $this->parameters[$parameterName] = $value;
  }

  public function whereClause(): ?string
  {
    if (empty($this->conditions)) {
      return null;
    }

    return implode(' AND ', $this->conditions);
  }

  public function parameters(): array
  {
    return $this->parameters;
  }
}

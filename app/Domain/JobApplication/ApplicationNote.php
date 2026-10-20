<?php

namespace App\Domain\JobApplication;

use App\Domain\Exception\InvalidValueException;
use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Internal remark written by the hiring team about an application. Never shown to the candidate.
 */
final class ApplicationNote
{
  private const MAX_LENGTH = 2000;

  private function __construct(
    public readonly JobApplicationId $applicationId,
    public readonly ?UserId $authorId,
    public readonly string $body,
    public readonly DateTimeImmutable $createdAt
  ) {
  }

  public static function write(
    JobApplicationId $applicationId,
    UserId $authorId,
    string $body,
    ?DateTimeImmutable $now = null
  ): self {
    $body = trim($body);
    if ($body === '') {
      throw new InvalidValueException('Escreva a nota antes de salvar.');
    }

    if (mb_strlen($body) > self::MAX_LENGTH) {
      throw new InvalidValueException('A nota deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.');
    }

    return new self($applicationId, $authorId, $body, $now ?? new DateTimeImmutable());
  }

  public static function reconstitute(
    JobApplicationId $applicationId,
    ?UserId $authorId,
    string $body,
    DateTimeImmutable $createdAt
  ): self {
    return new self($applicationId, $authorId, $body, $createdAt);
  }
}

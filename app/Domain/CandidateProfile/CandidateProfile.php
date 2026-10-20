<?php

namespace App\Domain\CandidateProfile;

use App\Domain\Shared\Guard;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Candidate profile aggregate (1:1 with User): bio, work area, seniority, work mode, social links and topic tags.
 */
final class CandidateProfile
{
  private const MAX_HEADLINE_LENGTH = 160;
  private const MAX_BIO_LENGTH = 3000;
  private const MAX_URL_LENGTH = 255;

  /**
   * @param list<TopicTag> $topics
   */
  private function __construct(
    private UserId $userId,
    private string $headline,
    private string $bio,
    private WorkArea $area,
    private Seniority $seniority,
    private WorkMode $workMode,
    private string $linkedinUrl,
    private string $githubUrl,
    private array $topics,
    private DateTimeImmutable $updatedAt
  ) {
  }

  /**
   * @param string|iterable<mixed> $topics
   */
  public static function define(
    UserId $userId,
    string $headline,
    string $bio,
    WorkArea $area,
    Seniority $seniority,
    WorkMode $workMode,
    string $linkedinUrl = '',
    string $githubUrl = '',
    string|iterable $topics = [],
    ?DateTimeImmutable $now = null
  ): self {
    return new self(
      $userId,
      self::normalizeHeadline($headline),
      self::normalizeBio($bio),
      $area,
      $seniority,
      $workMode,
      self::normalizeUrl($linkedinUrl, 'LinkedIn'),
      self::normalizeUrl($githubUrl, 'GitHub/Portfólio'),
      TopicTag::listFromInput($topics),
      $now ?? new DateTimeImmutable()
    );
  }

  public static function emptyFor(UserId $userId): self
  {
    return new self(
      $userId,
      '',
      '',
      WorkArea::Backend,
      Seniority::Pleno,
      WorkMode::Remoto,
      '',
      '',
      [],
      new DateTimeImmutable()
    );
  }

  /**
   * @param list<TopicTag> $topics
   */
  public static function reconstitute(
    UserId $userId,
    string $headline,
    string $bio,
    WorkArea $area,
    Seniority $seniority,
    WorkMode $workMode,
    string $linkedinUrl,
    string $githubUrl,
    array $topics,
    DateTimeImmutable $updatedAt
  ): self {
    return new self(
      $userId,
      trim($headline),
      trim($bio),
      $area,
      $seniority,
      $workMode,
      trim($linkedinUrl),
      trim($githubUrl),
      array_values($topics),
      $updatedAt
    );
  }

  public function isComplete(): bool
  {
    return ($this->headline !== '' || $this->bio !== '') && $this->topics !== [];
  }

  public function userId(): UserId
  {
    return $this->userId;
  }

  public function headline(): string
  {
    return $this->headline;
  }

  public function bio(): string
  {
    return $this->bio;
  }

  public function area(): WorkArea
  {
    return $this->area;
  }

  public function seniority(): Seniority
  {
    return $this->seniority;
  }

  public function workMode(): WorkMode
  {
    return $this->workMode;
  }

  public function linkedinUrl(): string
  {
    return $this->linkedinUrl;
  }

  public function githubUrl(): string
  {
    return $this->githubUrl;
  }

  /**
   * @return list<TopicTag>
   */
  public function topics(): array
  {
    return $this->topics;
  }

  /**
   * @return list<string>
   */
  public function topicLabels(): array
  {
    return array_map(static fn (TopicTag $t): string => $t->label(), $this->topics);
  }

  public function updatedAt(): DateTimeImmutable
  {
    return $this->updatedAt;
  }

  private static function normalizeHeadline(string $headline): string
  {
    return Guard::optionalText(
      $headline,
      self::MAX_HEADLINE_LENGTH,
      'O título profissional deve ter no máximo ' . self::MAX_HEADLINE_LENGTH . ' caracteres.'
    );
  }

  private static function normalizeBio(string $bio): string
  {
    return Guard::optionalText(
      $bio,
      self::MAX_BIO_LENGTH,
      'A apresentação do perfil deve ter no máximo ' . self::MAX_BIO_LENGTH . ' caracteres.'
    );
  }

  private static function normalizeUrl(string $url, string $label): string
  {
    return Guard::optionalText(
      $url,
      self::MAX_URL_LENGTH,
      'O link de ' . $label . ' deve ter no máximo ' . self::MAX_URL_LENGTH . ' caracteres.'
    );
  }
}

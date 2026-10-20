<?php

namespace App\Domain\JobApplication;

use App\Domain\Exception\InvalidValueException;

/**
 * Stage of an application in the hiring pipeline.
 *
 *   recebida ──► em_analise ──► entrevista ──► aprovada
 *      └────────────┴──────────────┴──► reprovada ──(reabrir)──► em_analise
 *      └────────────┴──────────────┴──► desistiu  ──(recandidatar)──► recebida
 *
 * Recruiters advance one stage at a time (no skipping).
 */
enum ApplicationStatus: string
{
  case Received = 'recebida';
  case InReview = 'em_analise';
  case Interview = 'entrevista';
  case Approved = 'aprovada';
  case Rejected = 'reprovada';
  case Withdrawn = 'desistiu';

  public static function fromString(string $value): self
  {
    return self::tryFrom(strtolower(trim($value)))
      ?? throw new InvalidValueException('Etapa da candidatura inválida.');
  }

  public static function fromStored(?string $value): self
  {
    return self::tryFrom(strtolower(trim((string) $value))) ?? self::Received;
  }

  public function canTransitionTo(self $to): bool
  {
    return in_array($to, $this->allowedTransitions(), true);
  }

  /**
   * Next stage when advancing (null when there is no forward stage).
   */
  public function next(): ?self
  {
    return match ($this) {
      self::Received => self::InReview,
      self::InReview => self::Interview,
      self::Interview => self::Approved,
      default => null,
    };
  }

  /**
   * Still being evaluated: can be advanced, rejected or withdrawn.
   */
  public function isInProcess(): bool
  {
    return in_array($this, [self::Received, self::InReview, self::Interview], true);
  }

  public function isClosed(): bool
  {
    return !$this->isInProcess();
  }

  /** Label used by recruiters on the board. */
  public function label(): string
  {
    return match ($this) {
      self::Received => 'Recebida',
      self::InReview => 'Em análise',
      self::Interview => 'Entrevista',
      self::Approved => 'Aprovada',
      self::Rejected => 'Reprovada',
      self::Withdrawn => 'Desistiu',
    };
  }

  /** Label shown to the candidate. */
  public function candidateLabel(): string
  {
    return match ($this) {
      self::Received => 'Candidatura enviada',
      self::InReview => 'Em análise',
      self::Interview => 'Em entrevista',
      self::Approved => 'Aprovada',
      self::Rejected => 'Não selecionada',
      self::Withdrawn => 'Você desistiu',
    };
  }

  /**
   * @return list<self>
   */
  private function allowedTransitions(): array
  {
    return match ($this) {
      self::Received => [self::InReview, self::Rejected, self::Withdrawn],
      self::InReview => [self::Interview, self::Rejected, self::Withdrawn],
      self::Interview => [self::Approved, self::Rejected, self::Withdrawn],
      self::Approved => [],
      self::Rejected => [self::InReview],
      self::Withdrawn => [self::Received],
    };
  }
}

<?php

namespace App\Domain\JobApplication;

use App\Domain\Exception\BusinessRuleViolationException;

final class InvalidApplicationTransitionException extends BusinessRuleViolationException
{
  public static function between(ApplicationStatus $from, ApplicationStatus $to): self
  {
    return new self(sprintf(
      'Não é possível mover a candidatura de "%s" para "%s".',
      $from->label(),
      $to->label()
    ));
  }

  public static function cannotAdvance(ApplicationStatus $from): self
  {
    return new self(sprintf('A candidatura em "%s" não tem próxima etapa.', $from->label()));
  }
}

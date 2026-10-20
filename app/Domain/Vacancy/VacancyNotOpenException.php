<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\BusinessRuleViolationException;

final class VacancyNotOpenException extends BusinessRuleViolationException
{
  public function __construct()
  {
    parent::__construct('A vaga informada não está disponível para candidatura.');
  }
}

<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\AbstractId;

final class VacancyId extends AbstractId
{
  protected static function label(): string
  {
    return 'Identificador de vaga';
  }
}

<?php

namespace App\Domain\JobApplication;

use App\Domain\Shared\AbstractId;

final class JobApplicationId extends AbstractId
{
  protected static function label(): string
  {
    return 'Identificador de candidatura';
  }
}

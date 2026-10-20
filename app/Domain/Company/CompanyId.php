<?php

namespace App\Domain\Company;

use App\Domain\Shared\AbstractId;

final class CompanyId extends AbstractId
{
  protected static function label(): string
  {
    return 'ID da empresa';
  }
}

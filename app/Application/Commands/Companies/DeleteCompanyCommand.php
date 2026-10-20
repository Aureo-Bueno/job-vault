<?php

namespace App\Application\Commands\Companies;

use App\Application\Abstractions\Command;

final class DeleteCompanyCommand implements Command
{
  public function __construct(public readonly string $companyId)
  {
  }
}

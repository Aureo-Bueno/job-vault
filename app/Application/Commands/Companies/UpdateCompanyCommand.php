<?php

namespace App\Application\Commands\Companies;

use App\Application\Abstractions\Command;

final class UpdateCompanyCommand implements Command
{
  public function __construct(
    public readonly string $companyId,
    public readonly string $name,
    public readonly string $sector = 'Tecnologia',
    public readonly string $website = '',
    public readonly string $description = ''
  ) {
  }
}

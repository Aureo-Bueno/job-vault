<?php

namespace App\Application\Commands\Companies;

use App\Application\Abstractions\Command;

final class CreateCompanyCommand implements Command
{
  public function __construct(
    public readonly string $name,
    public readonly string $sector = 'Tecnologia',
    public readonly string $website = '',
    public readonly string $description = ''
  ) {
  }
}

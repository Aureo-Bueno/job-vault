<?php

namespace App\Application\Features\Companies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Companies\DeleteCompanyCommand;
use App\Application\Exceptions\NotFoundException;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyRepositoryInterface;
use InvalidArgumentException;

final class DeleteCompanyHandler implements CommandHandlerInterface
{
  public function __construct(private CompanyRepositoryInterface $companies)
  {
  }

  public function commandClass(): string
  {
    return DeleteCompanyCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof DeleteCompanyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $id = CompanyId::fromString($command->companyId);
    if ($this->companies->get($id) === null) {
      throw new NotFoundException('Empresa não encontrada.');
    }

    $this->companies->remove($id);
  }
}

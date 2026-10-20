<?php

namespace App\Application\Features\Companies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Companies\CreateCompanyCommand;
use App\Application\DTOs\CompanyDto;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Exception\AlreadyExistsException;
use InvalidArgumentException;

final class CreateCompanyHandler implements CommandHandlerInterface
{
  public function __construct(private CompanyRepositoryInterface $companies)
  {
  }

  public function commandClass(): string
  {
    return CreateCompanyCommand::class;
  }

  public function handle(Command $command): CompanyDto
  {
    if (!$command instanceof CreateCompanyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $name = CompanyName::fromString($command->name);
    if ($this->companies->findByName($name) !== null) {
      throw new AlreadyExistsException('Já existe uma empresa cadastrada com este nome.');
    }

    $company = Company::register(
      CompanyId::generate(),
      $name,
      $command->sector,
      $command->website,
      $command->description
    );
    $this->companies->save($company);

    return CompanyDto::fromCompany($company);
  }
}

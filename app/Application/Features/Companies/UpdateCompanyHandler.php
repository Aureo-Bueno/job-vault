<?php

namespace App\Application\Features\Companies;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;
use App\Application\Commands\Companies\UpdateCompanyCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Security\Authorization;
use App\Application\Security\CurrentUserProvider;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class UpdateCompanyHandler implements CommandHandlerInterface
{
  public function __construct(
    private CompanyRepositoryInterface $companies,
    private ?UserRepositoryInterface $users = null,
    private ?CurrentUserProvider $currentUser = null,
    private ?Authorization $authorization = null
  ) {
  }

  public function commandClass(): string
  {
    return UpdateCompanyCommand::class;
  }

  public function handle(Command $command): void
  {
    if (!$command instanceof UpdateCompanyCommand) {
      throw new InvalidArgumentException('Invalid command for ' . self::class);
    }

    $companyId = CompanyId::fromString($command->companyId);
    $company = $this->companies->get($companyId);
    if ($company === null) {
      throw new NotFoundException('Empresa não encontrada.');
    }

    $logged = $this->currentUser?->currentUser();
    if ($logged !== null && $this->users !== null && ($this->authorization === null || !$this->authorization->isAdmin($logged->id))) {
      $user = $this->users->get(UserId::fromString($logged->id));
      if ($user === null || $user->companyId() === null || !$user->companyId()->equals($companyId)) {
        throw new ForbiddenException('Você só pode editar a empresa à qual está vinculado.');
      }
    }

    $name = CompanyName::fromString($command->name);
    $existing = $this->companies->findByName($name);
    if ($existing !== null && !$existing->id()->equals($company->id())) {
      throw new AlreadyExistsException('Já existe uma empresa cadastrada com este nome.');
    }

    $company->updateDetails($name, $command->sector, $command->website, $command->description);
    $this->companies->save($company);
  }
}

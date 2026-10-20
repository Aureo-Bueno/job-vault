<?php

namespace App\Application\Features\Vacancies;

use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Security\Authorization;
use App\Application\Security\CurrentUserProvider;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\Vacancy\Vacancy;

/**
 * Enforces company-level isolation for non-admin recruiters managing vacancies.
 */
final class VacancyAccessScope
{
  public function __construct(
    private ?CurrentUserProvider $currentUser = null,
    private ?UserRepositoryInterface $users = null,
    private ?Authorization $authorization = null,
    private ?CompanyRepositoryInterface $companies = null
  ) {
  }

  public function isAdmin(): bool
  {
    $logged = $this->currentUser?->currentUser();
    if ($logged === null || $this->authorization === null) {
      return true;
    }

    return $this->authorization->isAdmin($logged->id);
  }

  public function currentCompanyId(): ?CompanyId
  {
    $logged = $this->currentUser?->currentUser();
    if ($logged === null || $this->users === null) {
      return null;
    }

    $user = $this->users->get(UserId::fromString($logged->id));
    return $user?->companyId();
  }

  /**
   * Returns the company filter to apply on queries (null for admins, mandatory for recruiters).
   */
  public function resolveFilterCompany(?string $requestedCompanyId): ?CompanyId
  {
    if (!$this->isAdmin()) {
      $ownCompanyId = $this->currentCompanyId();
      if ($ownCompanyId === null) {
        throw new ForbiddenException('Sua conta não está vinculada a nenhuma empresa.');
      }

      return $ownCompanyId;
    }

    if ($requestedCompanyId !== null && trim($requestedCompanyId) !== '') {
      return CompanyId::fromString($requestedCompanyId);
    }

    return null;
  }

  public function resolveAssignedCompany(?string $explicitCompanyId): ?CompanyId
  {
    if (!$this->isAdmin()) {
      $ownCompanyId = $this->currentCompanyId();
      if ($ownCompanyId === null) {
        throw new ForbiddenException('Sua conta precisa estar vinculada a uma empresa para publicar vagas.');
      }

      return $ownCompanyId;
    }

    if ($explicitCompanyId === null || trim($explicitCompanyId) === '') {
      return $this->currentCompanyId();
    }

    $companyId = CompanyId::fromString($explicitCompanyId);
    if ($this->companies !== null && $this->companies->get($companyId) === null) {
      throw new NotFoundException('Empresa informada não encontrada.');
    }

    return $companyId;
  }

  public function assertCanManage(Vacancy $vacancy): void
  {
    if ($this->isAdmin()) {
      return;
    }

    $ownCompanyId = $this->currentCompanyId();
    if ($ownCompanyId === null || !$vacancy->belongsToCompany($ownCompanyId)) {
      throw new ForbiddenException('Você só pode gerenciar vagas da sua própria empresa.');
    }
  }
}

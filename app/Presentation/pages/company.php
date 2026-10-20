<?php

/**
 * Recruiter's own company profile page.
 */

use App\Application\Commands\Companies\UpdateCompanyCommand;
use App\Application\Exceptions\ForbiddenException;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\StatusAlertMapper;

$user = PageAccess::requireLogin();
if (!PageAccess::can($user, PageAccess::MANAGE_VACANCIES)) {
  throw new ForbiddenException('Apenas contas de empresa podem acessar os dados da empresa.');
}

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();
$company = $queryBus->ask(new GetMyCompanyQuery());

if ($company === null) {
  HttpRedirect::withError('home', new ForbiddenException('Sua conta não está vinculada a uma empresa.'));
}

$alerta = StatusAlertMapper::fromRequest();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $commandBus->dispatch(new UpdateCompanyCommand(
      $company->id,
      (string) ($_POST['name'] ?? ''),
      (string) ($_POST['sector'] ?? 'Tecnologia'),
      (string) ($_POST['website'] ?? ''),
      (string) ($_POST['description'] ?? '')
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null || ($alerta['type'] ?? '') === 'success') {
    HttpRedirect::withSuccess('company', 'Dados da empresa atualizados com sucesso.');
  }
}

$company = $queryBus->ask(new GetMyCompanyQuery()) ?? $company;

Layout::page('/pages/company-form.php', [
  'company' => $company,
  'alerta' => $alerta,
]);

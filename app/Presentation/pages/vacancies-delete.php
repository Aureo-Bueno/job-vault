<?php

/**
 * Vacancy deletion confirmation.
 */

use App\Application\Commands\Vacancies\DeleteVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$user = PageAccess::requireLogin();
PageAccess::requirePermission($user, 'vacancy.delete');

$vacancy = AppContainer::queryBus()->ask(new GetVacancyByIdQuery((string) ($_GET['id'] ?? '')));
if ($vacancy === null) {
  HttpRedirect::withError('home', new NotFoundException('Vaga não encontrada para exclusão.'));
}

if (isset($_POST['excluir'])) {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new DeleteVacancyCommand($vacancy->id));
  } catch (\Throwable $exception) {
    HttpRedirect::withError('home', $exception);
  }

  HttpRedirect::withSuccess('home', 'Vaga excluída com sucesso.');
}

Layout::page('/pages/vacancy-delete-confirm.php', ['vacancy' => $vacancy]);

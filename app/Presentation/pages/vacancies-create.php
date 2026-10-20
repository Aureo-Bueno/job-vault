<?php

/**
 * Vacancy creation form.
 */

use App\Application\Commands\Vacancies\CreateVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$user = PageAccess::requireLogin();
PageAccess::requirePermission($user, 'vacancy.create');

$vacancy = (object) ['title' => '', 'description' => '', 'isActive' => true];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $vacancy->title = (string) ($_POST['title'] ?? '');
  $vacancy->description = (string) ($_POST['description'] ?? '');
  $vacancy->isActive = ($_POST['vacancy_status'] ?? 'active') === 'active';

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new CreateVacancyCommand(
      $vacancy->title,
      $vacancy->description,
      $vacancy->isActive
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', 'Vaga cadastrada com sucesso.');
  }
}

Layout::page('/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Cadastrar Vaga',
  'alerta' => $alerta,
]);

<?php

/**
 * Vacancy edition form.
 */

use App\Application\Commands\Vacancies\UpdateVacancyCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Exceptions\NotFoundException;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;

$user = PageAccess::requireLogin();
PageAccess::requirePermission($user, 'vacancy.edit');

$found = AppContainer::queryBus()->ask(new GetVacancyByIdQuery((string) ($_GET['id'] ?? '')));
if ($found === null) {
  HttpRedirect::withError('home', new NotFoundException('Vaga não encontrada para edição.'));
}

$vacancy = (object) [
  'id' => $found->id,
  'title' => $found->title,
  'description' => $found->description,
  'isActive' => $found->isActive,
];
$alerta = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $vacancy->title = (string) ($_POST['title'] ?? '');
  $vacancy->description = (string) ($_POST['description'] ?? '');
  $vacancy->isActive = ($_POST['vacancy_status'] ?? 'active') === 'active';

  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    AppContainer::commandBus()->dispatch(new UpdateVacancyCommand(
      $vacancy->id,
      $vacancy->title,
      $vacancy->description,
      $vacancy->isActive
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null) {
    HttpRedirect::withSuccess('home', 'Vaga atualizada com sucesso.');
  }
}

Layout::page('/pages/vacancy-form.php', [
  'vacancy' => $vacancy,
  'tituloPagina' => 'Editar vaga',
  'alerta' => $alerta,
]);

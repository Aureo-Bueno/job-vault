<?php

/**
 * Candidate profile page: professional headline, bio, work area, seniority, work mode, links and topic tags.
 */

use App\Application\Commands\Profile\SaveMyCandidateProfileCommand;
use App\Application\Exceptions\MessageValidationException;
use App\Application\Queries\Profile\GetMyCandidateProfileQuery;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\Support\Csrf;
use App\Presentation\Support\ExceptionHttpMapper;
use App\Presentation\Support\HttpRedirect;
use App\Presentation\Support\Layout;
use App\Presentation\Support\PageAccess;
use App\Presentation\Support\StatusAlertMapper;

PageAccess::requireLogin();

$queryBus = AppContainer::queryBus();
$commandBus = AppContainer::commandBus();
$alerta = StatusAlertMapper::fromRequest();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  try {
    if (!Csrf::validateFromRequest()) {
      throw new MessageValidationException('Não foi possível validar a requisição. Tente novamente.');
    }

    $commandBus->dispatch(new SaveMyCandidateProfileCommand(
      (string) ($_POST['headline'] ?? ''),
      (string) ($_POST['bio'] ?? ''),
      (string) ($_POST['area'] ?? 'backend'),
      (string) ($_POST['seniority'] ?? 'pleno'),
      (string) ($_POST['work_mode'] ?? 'remoto'),
      (string) ($_POST['linkedin_url'] ?? ''),
      (string) ($_POST['github_url'] ?? ''),
      (string) ($_POST['topics'] ?? '')
    ));
  } catch (\Throwable $exception) {
    $alerta = ExceptionHttpMapper::toAlert($exception);
  }

  if ($alerta === null || ($alerta['type'] ?? '') === 'success') {
    HttpRedirect::withSuccess('profile', 'Perfil atualizado com sucesso! Seu match com as vagas já foi recalculado.');
  }
}

$profile = $queryBus->ask(new GetMyCandidateProfileQuery());

Layout::page('/pages/profile-form.php', [
  'profile' => $profile,
  'alerta' => $alerta,
  'areas' => WorkArea::options(),
  'seniorities' => Seniority::options(),
  'workModes' => WorkMode::options(),
  'popularTopics' => WorkArea::popularTopics(),
]);

<?php
require BASE_PATH . '/vendor/autoload.php';

use App\Domain\Model\Vaga;
use App\Infrastructure\Container\AppContainer;
use App\Presentation\View;

$tituloPagina = 'Cadastrar Vaga';

$authService = AppContainer::authService();
$authService->requireLogin();

$vagaService = AppContainer::vagaService();
$obVaga = new Vaga();

if (isset($_POST['titulo'], $_POST['descricao'], $_POST['ativo'])) {
  $obVaga->titulo   = $_POST['titulo'];
  $obVaga->descricao = $_POST['descricao'];
  $obVaga->ativo    = $_POST['ativo'];
  $vagaService->create($obVaga);

  header('location: index.php?r=home&status=success');
  exit;
}

View::render(VIEW_PATH . '/layout/header.php');
View::render(VIEW_PATH . '/pages/vacancy-form.php', [
  'obVaga' => $obVaga,
  'tituloPagina' => $tituloPagina
]);
View::render(VIEW_PATH . '/layout/footer.php');

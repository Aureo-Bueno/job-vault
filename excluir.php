<?php
require __DIR__ . '/vendor/autoload.php';

use \App\Session\Login;
use \App\Util\RoleManager;
use \App\Entity\Vaga;

// Require login
Login::requireLogin();
$usuarioId = Login::getUsuarioLogado()['id'];

// Check permission to delete
RoleManager::requirePermission($usuarioId, 'vaga.deletar');


if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
  header('location: index.php?status=error');
  exit;
}

$obVaga = Vaga::getVaga($_GET['id']);

if (!$obVaga instanceof Vaga) {
  header('location: index.php?status=error');
  exit;
}

if (isset($_POST['excluir'])) {
  $obVaga->exluir();


  header('location: index.php?status=success');
  exit;
}

include __DIR__ . '/includes/header.php';

include __DIR__ . '/includes/confirmar-exlusao.php';

include __DIR__ . '/includes/footer.php';

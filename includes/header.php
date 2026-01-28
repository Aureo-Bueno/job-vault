<?php

use \App\Session\Login;


$usuarioLogado = Login::getUsuarioLogado();

$usuario = $usuarioLogado ?
  $usuarioLogado['nome'] . '<a href="logout.php" class="text-light font-wight-bold ml-2">Sair</a>' :
  'Visitante <a href="login.php" class="text-light font-wight-bold ml-2">Entrar</a> ';
?>
<!Doctype html>
<html lang="pt-br">

<head>
  <title>Sistema - Vagas</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body class="bg-dark text-light">
  <div class="container">
    <div class="jumbotron bg-info">
      <h1>Sistema de Vagas</h1>
      <p>Exemplo Crud PHPOO</p>
      <hr class="border-light">
      <div class="d-flex justify-content-start">
        <?= $usuario ?>
      </div>
    </div>
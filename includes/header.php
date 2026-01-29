<?php

use App\Session\Login;
use App\Util\RoleManager;

$usuarioLogado = Login::getUsuarioLogado();

if ($usuarioLogado) {
  $role = RoleManager::getUserRole($usuarioLogado['id']);
  $roleName = $role ? $role->nome : 'Sem role';

  // Badge colors by role
  $badgeClass = match ($roleName) {
    'admin' => 'bg-danger',
    'gestor' => 'bg-warning text-dark',
    'usuario' => 'bg-success',
    default => 'bg-secondary'
  };
} else {
  $roleName = 'Visitante';
  $badgeClass = 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title>JobVault - Sistema de Vagas</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #0d6efd;
      --secondary-color: #6c757d;
      --success-color: #198754;
      --danger-color: #dc3545;
      --warning-color: #ffc107;
    }

    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar-custom {
      background: linear-gradient(90deg, rgba(13, 110, 253, 0.95) 0%, rgba(13, 110, 253, 0.85) 100%);
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
      padding: 1rem 0;
    }

    .navbar-brand {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.5px;
      background: linear-gradient(135deg, #fff 0%, #e9ecef 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .navbar-brand i {
      margin-right: 0.5rem;
      font-size: 2rem;
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      margin: 0 0.5rem;
    }

    .nav-link:hover {
      color: #fff !important;
    }

    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 0;
      height: 2px;
      background: #fff;
      transition: width 0.3s ease;
    }

    .nav-link:hover::after {
      width: 100%;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 1.5rem;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1.1rem;
    }

    .role-badge {
      padding: 0.35rem 0.75rem;
      border-radius: 20px;
      font-size: 0.775rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-logout {
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid rgba(220, 53, 69, 0.5);
      color: #ff6b6b !important;
      transition: all 0.3s ease;
      padding: 0.5rem 1rem;
      border-radius: 8px;
    }

    .btn-logout:hover {
      background: rgba(220, 53, 69, 0.3);
      border-color: rgba(220, 53, 69, 0.8);
      color: #fff !important;
      transform: translateY(-2px);
    }

    .btn-login {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      color: #fff !important;
      transition: all 0.3s ease;
      padding: 0.5rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .jumbotron-custom {
      background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%);
      border: 1px solid rgba(13, 110, 253, 0.2);
      border-radius: 12px;
      padding: 3rem 2rem;
      margin: 2rem 0;
      backdrop-filter: blur(10px);
    }

    .jumbotron-custom h1 {
      background: linear-gradient(135deg, #0d6efd 0%, #667eea 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
      font-size: 3rem;
      margin-bottom: 0.5rem;
    }

    .jumbotron-custom p {
      color: rgba(255, 255, 255, 0.75);
      font-size: 1.25rem;
      margin-bottom: 1.5rem;
    }

    .divider {
      height: 2px;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      margin: 1rem 0;
    }

    .container-content {
      margin-top: 2rem;
      margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
      .navbar-brand {
        font-size: 1.5rem;
      }

      .jumbotron-custom {
        padding: 2rem 1.5rem;
      }

      .jumbotron-custom h1 {
        font-size: 2rem;
      }

      .user-info {
        margin-top: 1rem;
        width: 100%;
        justify-content: space-between;
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid px-4">
      <!-- Brand -->
      <a class="navbar-brand" href="index.php">
        <i class="bi bi-briefcase-fill"></i>
        JobVault
      </a>

      <!-- Toggler -->
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Nav Items -->
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
          <li class="nav-item">
            <a class="nav-link" href="index.php">
              <i class="bi bi-house-fill"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="cadastrar.php">
              <i class="bi bi-plus-circle-fill"></i> Nova Vaga
            </a>
          </li>
          <!-- User Info / Login -->
          <li class="nav-item">
            <?php if ($usuarioLogado) : ?>
              <div class="user-info">
                <div class="user-avatar">
                  <?= substr($usuarioLogado['nome'], 0, 1) ?>
                </div>
                <div>
                  <div class="text-white fw-bold">
                    <?= htmlspecialchars($usuarioLogado['nome']) ?>
                  </div>
                  <span class="role-badge <?= $badgeClass ?>">
                    <i class="bi bi-shield-fill"></i>
                    <?= strtoupper($roleName) ?>
                  </span>
                </div>
                <a href="logout.php" class="btn btn-logout">
                  <i class="bi bi-box-arrow-right"></i>
                </a>
              </div>
            <?php else : ?>
              <a href="login.php" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
              </a>
            <?php endif; ?>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Header Jumbotron -->
  <div class="container">
    <div class="jumbotron-custom">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1>
            <i class="bi bi-briefcase"></i> JobVault
          </h1>
          <p class="mb-0">Sistema de Gerenciamento de Vagas</p>
        </div>
        <?php if ($usuarioLogado && $role) : ?>
          <div class="text-end">
            <small class="text-muted d-block">Seu Nível:</small>
            <span class="role-badge <?= $badgeClass ?>" style="font-size: 1rem;">
              <?= strtoupper($roleName) ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
      <div class="divider"></div>
      <p class="text-muted mb-0">
        <i class="bi bi-lightning-charge-fill text-warning"></i>
        Encontre as melhores oportunidades de carreira
      </p>
    </div>
  </div>

  <!-- Main Content Container -->
  <div class="container-content">
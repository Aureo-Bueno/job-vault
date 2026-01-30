<?php

use App\Infrastructure\Container\AppContainer;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'] ?? null;
$canViewUsers = $userId ? RoleManager::hasPermission($userId, 'user.list') : false;
$canCreateVacancy = $userId ? RoleManager::hasPermission($userId, 'vacancy.create') : false;
$canManageVacancies = $userId ? (RoleManager::isAdmin($userId) || RoleManager::isManager($userId)) : false;
$homeRoute = $loggedUser && !$canManageVacancies ? 'vacancies/apply' : 'home';

if ($loggedUser) {
  $role = RoleManager::getUserRole($loggedUser['id']);
  $roleName = $role ? $role->name : 'Sem role';

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
      --blue-700: #1d4ed8;
      --blue-600: #2563eb;
      --blue-500: #3b82f6;
      --bg: #0f172a;
      --surface: #111827;
      --surface-2: #0b1220;
      --border: #1f2937;
      --text: #e5e7eb;
      --muted: #9ca3af;
      --bs-primary: #2563eb;
      --bs-primary-rgb: 37, 99, 235;
    }

    body {
      background: var(--bg);
      min-height: 100vh;
      color: var(--text);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    a {
      color: var(--blue-500);
    }

    .text-muted {
      color: var(--muted) !important;
    }

    .navbar-custom {
      background: var(--blue-700);
      box-shadow: none;
      padding: 0.75rem 0;
    }

    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff !important;
    }

    .navbar-brand i {
      margin-right: 0.5rem;
      font-size: 1.6rem;
    }

    .navbar-custom .nav-link {
      color: rgba(255, 255, 255, 0.9) !important;
      font-weight: 500;
      margin: 0 0.25rem;
    }

    .navbar-custom .nav-link:hover {
      color: #fff !important;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem 1rem;
      background: var(--surface);
      border-radius: 999px;
      border: 1px solid var(--border);
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--blue-600);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: bold;
    }

    .role-badge {
      padding: 0.25rem 0.6rem;
      border-radius: 999px;
      font-size: 0.775rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .role-badge-lg {
      font-size: 0.9rem;
    }

    .btn-logout {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #fff !important;
      padding: 0.4rem 0.9rem;
      border-radius: 8px;
    }

    .btn-logout:hover {
      background: rgba(255, 255, 255, 0.08);
    }

    .btn-login {
      background: var(--blue-600);
      border: 1px solid var(--blue-600);
      color: #fff !important;
      padding: 0.4rem 1rem;
      border-radius: 8px;
      font-weight: 600;
    }

    .btn-login:hover {
      background: var(--blue-700);
      border-color: var(--blue-700);
    }

    .jumbotron-custom {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem;
      margin: 2rem 0;
    }

    .jumbotron-custom h1 {
      color: #fff;
      font-weight: 700;
      font-size: 2.4rem;
      margin-bottom: 0.25rem;
    }

    .jumbotron-custom p {
      color: var(--muted);
      margin-bottom: 0;
    }

    .divider {
      height: 1px;
      background: var(--border);
      margin: 1rem 0;
    }

    .container-content {
      margin-top: 2rem;
      margin-bottom: 2rem;
    }

    hr {
      border-color: var(--border);
      opacity: 1;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
    }

    .table {
      color: var(--text);
    }

    .table thead th {
      color: #fff;
      border-bottom: 1px solid var(--border);
    }

    .table tbody tr {
      border-bottom: 1px solid var(--border);
    }

    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.05);
    }

    .form-control,
    .form-select,
    textarea {
      background: var(--surface-2);
      border: 1px solid var(--border);
      color: var(--text);
    }

    .form-control:focus,
    .form-select:focus,
    textarea:focus {
      background: var(--surface-2);
      border-color: var(--blue-500);
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.2);
      color: var(--text);
    }

    .form-control::placeholder,
    textarea::placeholder {
      color: var(--muted);
    }

    textarea {
      resize: vertical;
      min-height: 150px;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.95rem;
    }

    .form-check-input {
      background-color: var(--surface-2);
      border: 1px solid var(--border);
    }

    .form-check-input:checked {
      background-color: var(--blue-600);
      border-color: var(--blue-600);
    }

    .form-check-input:focus {
      border-color: var(--blue-500);
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.2);
    }

    .nav-pills .nav-link {
      background: var(--surface-2);
      border: 1px solid var(--border);
      color: var(--muted) !important;
    }

    .nav-pills .nav-link.active {
      background: var(--blue-600);
      border-color: var(--blue-600);
      color: #fff !important;
    }

    .input-group .btn {
      background: var(--surface-2);
      border: 1px solid var(--border);
      color: var(--muted);
    }

    .input-group .btn:hover,
    .input-group .btn:focus {
      background: var(--surface);
      color: #fff;
      border-color: var(--border);
    }

    .btn-primary {
      background: var(--blue-600);
      border-color: var(--blue-600);
    }

    .btn-primary:hover {
      background: var(--blue-700);
      border-color: var(--blue-700);
    }

    .btn-outline-secondary {
      border-color: var(--border);
      color: var(--muted);
    }

    .btn-outline-secondary:hover {
      background: var(--surface);
      color: #fff;
    }

    .btn-outline-secondary:focus {
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.2);
    }

    .alert {
      border-radius: 10px;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.12);
      border-color: rgba(16, 185, 129, 0.35);
      color: #d1fae5;
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.12);
      border-color: rgba(239, 68, 68, 0.35);
      color: #fee2e2;
    }

    .alert-info {
      background: rgba(59, 130, 246, 0.12);
      border-color: rgba(59, 130, 246, 0.35);
      color: #dbeafe;
    }

    .alert .btn-close {
      filter: invert(1);
    }

    @media (max-width: 768px) {
      .navbar-brand {
        font-size: 1.25rem;
      }

      .jumbotron-custom {
        padding: 1.5rem;
      }

      .jumbotron-custom h1 {
        font-size: 1.75rem;
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
      <a class="navbar-brand" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
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
            <a class="nav-link" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
              <i class="bi bi-house-fill"></i> Home
            </a>
          </li>
          <?php if ($loggedUser && !$canManageVacancies) : ?>
            <li class="nav-item">
              <a class="nav-link" href="index.php?r=vacancies/apply">
                <i class="bi bi-send-check-fill"></i> Candidatar
              </a>
            </li>
          <?php endif; ?>
          <?php if ($canCreateVacancy) : ?>
            <li class="nav-item">
              <a class="nav-link" href="index.php?r=vacancies/new">
                <i class="bi bi-plus-circle-fill"></i> Nova Vaga
              </a>
            </li>
          <?php endif; ?>
          <?php if ($canViewUsers) : ?>
            <li class="nav-item">
              <a class="nav-link" href="index.php?r=users">
                <i class="bi bi-people-fill"></i> Usuários
              </a>
            </li>
          <?php endif; ?>
          <!-- User Info / Login -->
          <li class="nav-item">
            <?php if ($loggedUser) : ?>
              <div class="user-info">
                <div class="user-avatar">
                  <?= substr($loggedUser['name'], 0, 1) ?>
                </div>
                <div>
                  <div class="text-white fw-bold">
                    <?= htmlspecialchars($loggedUser['name']) ?>
                  </div>
                  <span class="role-badge <?= $badgeClass ?>">
                    <i class="bi bi-shield-fill"></i>
                    <?= strtoupper($roleName) ?>
                  </span>
                </div>
                <a href="index.php?r=logout" class="btn btn-logout">
                  <i class="bi bi-box-arrow-right"></i>
                </a>
              </div>
            <?php else : ?>
              <a href="index.php?r=login" class="btn btn-login">
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
        <?php if ($loggedUser && $role) : ?>
          <div class="text-end">
            <small class="text-muted d-block">Seu Nível:</small>
            <span class="role-badge role-badge-lg <?= $badgeClass ?>">
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

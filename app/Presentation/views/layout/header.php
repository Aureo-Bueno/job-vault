<?php

use App\Infrastructure\Container\AppContainer;
use App\Util\RoleManager;

$authService = AppContainer::authService();
$loggedUser = $authService->getLoggedUser();
$userId = $loggedUser['id'] ?? null;
$canViewUsers = $userId
  ? (RoleManager::isAdmin($userId) && RoleManager::hasPermission($userId, 'user.list'))
  : false;
$canCreateVacancy = $userId ? RoleManager::hasPermission($userId, 'vacancy.create') : false;
$canManageVacancies = $userId ? (RoleManager::isAdmin($userId) || RoleManager::isManager($userId)) : false;
$homeRoute = $loggedUser && !$canManageVacancies ? 'vacancies/apply' : 'home';
$currentRoute = $_GET['r'] ?? 'home';
$role = null;

if ($loggedUser) {
  $role = RoleManager::getUserRole($loggedUser['id']);
  $roleName = $role ? $role->name : 'Sem role';

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

$homeActive = $currentRoute === $homeRoute || ($homeRoute === 'home' && $currentRoute === '');
$vacanciesActive = str_starts_with($currentRoute, 'vacancies') && $currentRoute !== 'vacancies/apply';
$applyActive = $currentRoute === 'vacancies/apply';
$usersActive = str_starts_with($currentRoute, 'users');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <title>JobVault - Sistema de Vagas</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root {
      --color-primary: #0369a1;
      --color-secondary: #0ea5e9;
      --color-accent: #16a34a;
      --color-danger: #dc2626;
      --color-bg: #0b1121;
      --color-bg-soft: #0f172a;
      --color-surface: #111c33;
      --color-surface-2: #1a2945;
      --color-border: #25426f;
      --color-text: #e0ecff;
      --color-muted: #98aeca;
      --color-ring: rgba(14, 165, 233, 0.32);
      --radius-md: 14px;
      --radius-lg: 18px;
      --shadow-soft: 0 20px 40px rgba(0, 0, 0, 0.3);
      --bs-primary: #0369a1;
      --bs-primary-rgb: 3, 105, 161;
      --bs-success: #16a34a;
      --bs-success-rgb: 22, 163, 74;
      --bs-danger: #dc2626;
      --bs-danger-rgb: 220, 38, 38;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      color: var(--color-text);
      font-family: 'Fira Sans', sans-serif;
      background:
        radial-gradient(circle at 10% 10%, rgba(14, 165, 233, 0.17), transparent 40%),
        radial-gradient(circle at 90% 20%, rgba(22, 163, 74, 0.12), transparent 40%),
        linear-gradient(145deg, #0a1020, #111c33 56%, #091625);
      background-attachment: fixed;
    }

    a {
      color: var(--color-secondary);
      text-decoration: none;
      transition: color 180ms ease;
    }

    a:hover {
      color: #7dd3fc;
    }

    .text-muted {
      color: var(--color-muted) !important;
    }

    .brand-font {
      font-family: 'Fira Code', monospace;
      letter-spacing: 0.3px;
    }

    .navbar-custom {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: rgba(11, 17, 33, 0.82);
      border-bottom: 1px solid rgba(37, 66, 111, 0.85);
      backdrop-filter: blur(9px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
      padding: 0.72rem 0;
    }

    .navbar-brand {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      font-size: 1.24rem;
      font-weight: 600;
      color: #fff !important;
    }

    .navbar-brand i {
      color: #38bdf8;
    }

    .navbar-custom .nav-link {
      color: rgba(224, 236, 255, 0.86) !important;
      border-radius: 9px;
      font-weight: 500;
      padding: 0.46rem 0.72rem;
      transition: background-color 180ms ease, color 180ms ease;
    }

    .navbar-custom .nav-link:hover {
      background: rgba(14, 165, 233, 0.14);
      color: #fff !important;
    }

    .navbar-custom .nav-link.active {
      background: rgba(14, 165, 233, 0.22);
      color: #fff !important;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 0.72rem;
      padding: 0.42rem 0.54rem 0.42rem 0.42rem;
      background: rgba(17, 28, 51, 0.9);
      border-radius: 999px;
      border: 1px solid rgba(125, 211, 252, 0.24);
    }

    .user-avatar {
      width: 35px;
      height: 35px;
      border-radius: 999px;
      background: linear-gradient(140deg, var(--color-primary), var(--color-secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      text-transform: uppercase;
    }

    .role-badge {
      padding: 0.2rem 0.58rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }

    .role-badge-lg {
      font-size: 0.82rem;
    }

    .btn-logout {
      width: 34px;
      height: 34px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(220, 38, 38, 0.4);
      color: #fecaca !important;
      background: rgba(220, 38, 38, 0.12);
      transition: background-color 180ms ease, border-color 180ms ease;
    }

    .btn-logout:hover {
      border-color: rgba(220, 38, 38, 0.55);
      background: rgba(220, 38, 38, 0.2);
    }

    .btn-login {
      padding: 0.48rem 0.96rem;
      border-radius: 10px;
      border: 1px solid rgba(14, 165, 233, 0.46);
      color: #fff !important;
      background: linear-gradient(140deg, var(--color-primary), #0284c7);
      font-weight: 600;
      transition: filter 180ms ease;
    }

    .btn-login:hover {
      filter: brightness(1.05);
      color: #fff;
    }

    .hero-shell {
      margin-top: 1rem;
      margin-bottom: 0.75rem;
    }

    .jumbotron-custom {
      border-radius: var(--radius-lg);
      border: 1px solid rgba(125, 211, 252, 0.22);
      background:
        linear-gradient(120deg, rgba(3, 105, 161, 0.22), rgba(22, 163, 74, 0.08)),
        rgba(17, 28, 51, 0.86);
      padding: 1.55rem;
      box-shadow: var(--shadow-soft);
    }

    .jumbotron-custom h1 {
      margin: 0;
      color: #fff;
      font-weight: 700;
      font-size: clamp(1.45rem, 2.6vw, 2.15rem);
      line-height: 1.14;
    }

    .jumbotron-custom p {
      color: var(--color-muted);
      margin-top: 0.5rem;
      margin-bottom: 0;
      font-size: 0.97rem;
    }

    .hero-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      border-radius: 999px;
      padding: 0.35rem 0.72rem;
      border: 1px solid rgba(125, 211, 252, 0.34);
      background: rgba(2, 132, 199, 0.12);
      color: #bae6fd;
      font-size: 0.78rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.35px;
    }

    .divider {
      height: 1px;
      margin: 1rem 0 0.85rem;
      background: linear-gradient(to right, rgba(186, 230, 253, 0.42), transparent 78%);
    }

    .container-content {
      margin-inline: auto;
      width: min(1160px, calc(100% - 1.8rem));
    }

    .page-section {
      margin-top: 1rem;
      margin-bottom: 2.4rem;
      animation: fade-slide 260ms ease;
    }

    .section-head {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.1rem;
    }

    .section-title {
      margin: 0;
      color: #fff;
      font-size: clamp(1.35rem, 2vw, 1.74rem);
      font-weight: 700;
      line-height: 1.2;
    }

    .section-subtitle {
      margin: 0.45rem 0 0;
      color: var(--color-muted);
      font-size: 0.96rem;
    }

    .surface-card,
    .card {
      border: 1px solid rgba(37, 66, 111, 0.88);
      border-radius: var(--radius-md);
      background: rgba(17, 28, 51, 0.88);
      box-shadow: 0 12px 26px rgba(0, 0, 0, 0.22);
    }

    .card-body {
      padding: 1.2rem;
    }

    .toolbar-card {
      margin-bottom: 1.1rem;
    }

    .table {
      margin-bottom: 0;
      color: var(--color-text);
      vertical-align: middle;
    }

    .table thead th {
      border-bottom: 1px solid rgba(125, 211, 252, 0.16);
      color: #d3e8ff;
      font-size: 0.79rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.35px;
      white-space: nowrap;
      padding-top: 0.9rem;
      padding-bottom: 0.9rem;
    }

    .table tbody tr {
      border-bottom: 1px solid rgba(37, 66, 111, 0.54);
      transition: background-color 180ms ease;
    }

    .table tbody tr:hover {
      background: rgba(14, 165, 233, 0.09);
    }

    .table td,
    .table th {
      padding: 0.95rem 1rem;
    }

    .table-responsive {
      border-radius: var(--radius-md);
    }

    .empty-state {
      text-align: center;
      color: var(--color-muted);
      padding: 2rem 1rem;
    }

    .empty-state i {
      font-size: 1.15rem;
      color: #7dd3fc;
      margin-right: 0.3rem;
    }

    .form-label {
      color: #d2e7ff !important;
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: 0.2px;
      margin-bottom: 0.44rem;
    }

    .form-control,
    .form-select,
    textarea {
      background: var(--color-surface-2);
      border: 1px solid rgba(125, 211, 252, 0.22);
      color: var(--color-text);
      border-radius: 10px;
      min-height: 44px;
      transition: border-color 180ms ease, box-shadow 180ms ease;
    }

    .form-control::placeholder,
    textarea::placeholder {
      color: #7e95b2;
    }

    .form-control:focus,
    .form-select:focus,
    textarea:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 0.19rem var(--color-ring);
      background: var(--color-surface-2);
      color: var(--color-text);
    }

    textarea {
      min-height: 150px;
      resize: vertical;
    }

    .input-group .btn {
      color: #c2dbfa;
      background: rgba(11, 17, 33, 0.6);
      border-color: rgba(125, 211, 252, 0.22);
    }

    .input-group .btn:hover,
    .input-group .btn:focus {
      color: #fff;
      background: rgba(14, 165, 233, 0.16);
      border-color: rgba(125, 211, 252, 0.32);
    }

    .btn {
      border-radius: 10px;
      font-weight: 600;
      letter-spacing: 0.1px;
      min-height: 42px;
      transition: transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
    }

    .btn:hover {
      transform: translateY(-1px);
    }

    .btn-primary {
      background: linear-gradient(140deg, var(--color-primary), #0284c7);
      border-color: #0ea5e9;
    }

    .btn-primary:hover {
      background: linear-gradient(140deg, #0369a1, #0369a1);
      border-color: #38bdf8;
      box-shadow: 0 8px 16px rgba(2, 132, 199, 0.24);
    }

    .btn-success {
      background: linear-gradient(140deg, #15803d, var(--color-accent));
      border-color: #22c55e;
    }

    .btn-success:hover {
      background: #15803d;
      border-color: #22c55e;
      box-shadow: 0 8px 16px rgba(22, 163, 74, 0.24);
    }

    .btn-danger {
      background: linear-gradient(140deg, #b91c1c, var(--color-danger));
      border-color: #ef4444;
    }

    .btn-danger:hover {
      background: #b91c1c;
      border-color: #ef4444;
      box-shadow: 0 8px 16px rgba(220, 38, 38, 0.24);
    }

    .btn-outline-secondary {
      border-color: rgba(125, 211, 252, 0.24);
      color: #d2e7ff;
      background: rgba(11, 17, 33, 0.45);
    }

    .btn-outline-secondary:hover {
      border-color: rgba(125, 211, 252, 0.4);
      color: #fff;
      background: rgba(14, 165, 233, 0.14);
    }

    .form-check-input {
      background-color: var(--color-surface-2);
      border-color: rgba(125, 211, 252, 0.26);
    }

    .form-check-input:checked {
      background-color: #0284c7;
      border-color: #0284c7;
    }

    .form-check-input:focus {
      box-shadow: 0 0 0 0.19rem var(--color-ring);
      border-color: #38bdf8;
    }

    .nav-pills .nav-link {
      border-radius: 10px;
      border: 1px solid rgba(125, 211, 252, 0.2);
      background: rgba(17, 28, 51, 0.45);
      color: var(--color-muted) !important;
      font-weight: 600;
    }

    .nav-pills .nav-link.active {
      background: linear-gradient(140deg, var(--color-primary), #0284c7);
      color: #fff !important;
      border-color: #0ea5e9;
    }

    .alert {
      border-radius: 11px;
      border-width: 1px;
    }

    .alert-success {
      background: rgba(22, 163, 74, 0.16);
      border-color: rgba(134, 239, 172, 0.4);
      color: #dcfce7;
    }

    .alert-danger {
      background: rgba(220, 38, 38, 0.16);
      border-color: rgba(252, 165, 165, 0.42);
      color: #fee2e2;
    }

    .alert-info {
      background: rgba(3, 105, 161, 0.2);
      border-color: rgba(125, 211, 252, 0.4);
      color: #dbeafe;
    }

    .alert .btn-close {
      filter: invert(1);
      opacity: 0.8;
    }

    .status-badge {
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 600;
      padding: 0.28rem 0.6rem;
      letter-spacing: 0.2px;
      white-space: nowrap;
    }

    .app-pagination .btn {
      min-width: 42px;
      padding-inline: 0.78rem;
    }

    .app-footer {
      margin-top: 2.8rem;
      border-top: 1px solid rgba(125, 211, 252, 0.16);
      background: rgba(11, 17, 33, 0.7);
      backdrop-filter: blur(6px);
    }

    .app-footer .text-muted {
      color: #8fa8c5 !important;
    }

    .focus-ring:focus-visible,
    .btn:focus-visible,
    .form-control:focus-visible,
    .form-select:focus-visible,
    .nav-link:focus-visible {
      outline: 2px solid #7dd3fc;
      outline-offset: 2px;
    }

    @keyframes fade-slide {
      from {
        opacity: 0;
        transform: translateY(8px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (prefers-reduced-motion: reduce) {
      * {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
      }
    }

    @media (max-width: 992px) {
      .navbar-custom .navbar-collapse {
        margin-top: 0.6rem;
      }

      .user-info {
        width: 100%;
        justify-content: space-between;
      }

      .jumbotron-custom {
        padding: 1.15rem;
      }

      .container-content {
        width: min(1160px, calc(100% - 1.1rem));
      }
    }

    @media (max-width: 768px) {
      .section-head {
        align-items: flex-start;
      }

      .table th,
      .table td {
        padding: 0.75rem;
      }

      .page-section {
        margin-top: 0.6rem;
      }

      .hero-shell {
        margin-top: 0.7rem;
      }
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-content">
      <a class="navbar-brand brand-font" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
        <i class="bi bi-briefcase-fill"></i>
        JobVault
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegação">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
          <li class="nav-item">
            <a class="nav-link <?= $homeActive ? 'active' : '' ?>" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
              <i class="bi bi-house-fill"></i> Início
            </a>
          </li>

          <?php if ($canManageVacancies) : ?>
            <li class="nav-item">
              <a class="nav-link <?= $vacanciesActive ? 'active' : '' ?>" href="index.php?r=home">
                <i class="bi bi-briefcase-fill"></i> Vagas
              </a>
            </li>
          <?php endif; ?>

          <?php if ($loggedUser && !$canManageVacancies) : ?>
            <li class="nav-item">
              <a class="nav-link <?= $applyActive ? 'active' : '' ?>" href="index.php?r=vacancies/apply">
                <i class="bi bi-send-check-fill"></i> Candidatar
              </a>
            </li>
          <?php endif; ?>

          <?php if ($canCreateVacancy) : ?>
            <li class="nav-item">
              <a class="nav-link <?= $currentRoute === 'vacancies/new' ? 'active' : '' ?>" href="index.php?r=vacancies/new">
                <i class="bi bi-plus-circle-fill"></i> Nova vaga
              </a>
            </li>
          <?php endif; ?>

          <?php if ($canViewUsers) : ?>
            <li class="nav-item">
              <a class="nav-link <?= $usersActive ? 'active' : '' ?>" href="index.php?r=users">
                <i class="bi bi-people-fill"></i> Usuários
              </a>
            </li>
          <?php endif; ?>

          <li class="nav-item">
            <?php if ($loggedUser) : ?>
              <div class="user-info">
                <div class="user-avatar">
                  <?= htmlspecialchars(substr($loggedUser['name'], 0, 1)) ?>
                </div>
                <div>
                  <div class="text-white fw-semibold small lh-1 mb-1"><?= htmlspecialchars($loggedUser['name']) ?></div>
                  <span class="role-badge <?= htmlspecialchars($badgeClass) ?>">
                    <i class="bi bi-shield-fill"></i>
                    <?= htmlspecialchars(strtoupper($roleName)) ?>
                  </span>
                </div>
                <a href="index.php?r=logout" class="btn btn-logout" aria-label="Sair da sessão">
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

  <?php if ($currentRoute !== 'login') : ?>
    <div class="container-content hero-shell">
      <header class="jumbotron-custom">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
          <div>
            <h1 class="brand-font"><i class="bi bi-briefcase"></i> Gestão de vagas sem ruído</h1>
            <p>Painel central para cadastro, publicação e acompanhamento de candidaturas.</p>
          </div>
          <?php if ($loggedUser && $role) : ?>
            <span class="hero-chip">
              <i class="bi bi-person-badge-fill"></i>
              Perfil: <?= htmlspecialchars(strtoupper($roleName)) ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="divider"></div>
        <p class="mb-0">
          <i class="bi bi-lightning-charge-fill text-warning"></i>
          Fluxo direto para operações de RH: mais clareza, menos clique desnecessário.
        </p>
      </header>
    </div>
  <?php endif; ?>

  <main class="page-section">

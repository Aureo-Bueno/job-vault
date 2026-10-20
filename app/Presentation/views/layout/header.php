<?php
/**
 * Layout header. Data provided by App\Presentation\Support\Layout:
 * @var \App\Application\Security\AuthenticatedUser|null $loggedUser
 * @var string $roleName
 * @var bool $hasRole
 * @var bool $canViewUsers
 * @var bool $canCreateVacancy
 * @var bool $canManageVacancies
 * @var bool $hasCompany
 * @var string $companyName
 * @var bool $profileComplete
 * @var string $homeRoute
 * @var string $currentRoute
 */
$asset = static function (string $path): string {
  $file = BASE_PATH . '/public/' . $path;
  return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
};

$isActive = static fn (string ...$routes): string => in_array($currentRoute, $routes, true) ? 'active' : '';
$roleBadge = 'jv-badge--' . preg_replace('/[^a-z]/', '', strtolower($roleName));
$showShell = $loggedUser !== null;
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="dark">
  <title>JobVault</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..700&family=Figtree:wght@400;500;600;700&family=Fira+Code:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>">
</head>

<body>
  <a class="visually-hidden-focusable" href="#conteudo">Pular para o conteúdo</a>

<?php if ($showShell) : ?>
  <div class="jv-shell">
    <aside class="jv-sidebar">
      <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="jvNav" aria-labelledby="jvNavLabel">
        <div class="offcanvas-body p-0">
          <div class="jv-sidebar-inner">
            <div class="d-flex align-items-center justify-content-between">
              <a class="jv-brand" id="jvNavLabel" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
                <span class="jv-brand-mark"><i class="bi bi-briefcase-fill"></i></span>
                JobVault
              </a>
              <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#jvNav" aria-label="Fechar menu"></button>
            </div>

            <nav class="jv-nav" aria-label="Navegação principal">
              <div class="jv-nav-group">
                <p class="jv-nav-label">Vagas</p>
                <?php if ($canManageVacancies) : ?>
                  <a class="jv-nav-link <?= $isActive('home', 'vacancies', 'vacancies/edit', 'vacancies/delete', 'vacancies/applicants') ?>" href="index.php?r=home">
                    <i class="bi bi-grid-1x2"></i> Painel de vagas
                  </a>
                <?php endif; ?>
                <?php if ($canCreateVacancy) : ?>
                  <a class="jv-nav-link <?= $isActive('vacancies/new') ?>" href="index.php?r=vacancies/new">
                    <i class="bi bi-plus-square"></i> Nova vaga
                  </a>
                <?php endif; ?>
                <?php if ($hasCompany) : ?>
                  <a class="jv-nav-link <?= $isActive('company') ?>" href="index.php?r=company">
                    <i class="bi bi-building"></i> Minha empresa
                  </a>
                <?php endif; ?>
                <?php if (!$canManageVacancies) : ?>
                  <a class="jv-nav-link <?= $isActive('vacancies/apply') ?>" href="index.php?r=vacancies/apply">
                    <i class="bi bi-send"></i> Vagas abertas
                  </a>
                  <a class="jv-nav-link <?= $isActive('applications') ?>" href="index.php?r=applications">
                    <i class="bi bi-kanban"></i> Minhas candidaturas
                  </a>
                  <a class="jv-nav-link <?= $isActive('profile') ?>" href="index.php?r=profile">
                    <i class="bi bi-person-badge"></i>
                    <span class="flex-grow-1">Meu perfil</span>
                    <?php if (!$profileComplete) : ?>
                      <span class="jv-dot-warn" title="Complete seu perfil para calcular o match"></span>
                    <?php endif; ?>
                  </a>
                <?php endif; ?>
              </div>

              <?php if ($canViewUsers) : ?>
                <div class="jv-nav-group">
                  <p class="jv-nav-label">Administração</p>
                  <a class="jv-nav-link <?= $isActive('users', 'users/new', 'users/edit', 'users/delete') ?>" href="index.php?r=users">
                    <i class="bi bi-shield-lock"></i> Usuários e empresas
                  </a>
                </div>
              <?php endif; ?>
            </nav>

            <div class="jv-user">
              <span class="jv-avatar" aria-hidden="true"><?= htmlspecialchars($loggedUser->initial()) ?></span>
              <div class="jv-user-meta">
                <span class="jv-user-name"><?= htmlspecialchars($loggedUser->name) ?></span>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                  <span class="jv-badge <?= htmlspecialchars($roleBadge) ?>"><?= htmlspecialchars($roleName) ?></span>
                  <?php if ($companyName !== '') : ?>
                    <span class="jv-badge jv-badge--info text-truncate" style="max-width: 110px;" title="<?= htmlspecialchars($companyName) ?>">
                      <?= htmlspecialchars($companyName) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <a href="index.php?r=logout" class="jv-icon-btn jv-icon-btn--danger" title="Sair" aria-label="Sair da conta">
                <i class="bi bi-box-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <div class="jv-main">
      <header class="jv-topbar d-lg-none">
        <a class="jv-brand" href="index.php?r=<?= htmlspecialchars($homeRoute) ?>">
          <span class="jv-brand-mark"><i class="bi bi-briefcase-fill"></i></span>
          JobVault
        </a>
        <button class="jv-icon-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#jvNav" aria-controls="jvNav" aria-label="Abrir menu">
          <i class="bi bi-list fs-4"></i>
        </button>
      </header>

      <main class="jv-content jv-fade-in" id="conteudo">
<?php else : ?>
  <main id="conteudo">
<?php endif; ?>

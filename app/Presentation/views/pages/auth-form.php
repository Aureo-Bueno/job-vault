<?php
/**
 * @var string $alertaLogin
 * @var string $alertaCadastro
 * @var string $activeTab  'login'|'signup'
 * @var string $loginEmail
 * @var string $signupName
 * @var string $signupEmail
 * @var string $accountType 'candidate'|'company'
 * @var string $companyName
 * @var string $companySector
 */
use App\Presentation\Support\Csrf;

$isSignup = ($activeTab ?? 'login') === 'signup';
$isCompany = ($accountType ?? 'candidate') === 'company';
?>

<div class="jv-auth">
  <section class="jv-auth-aside" aria-label="Sobre o JobVault">
    <span class="jv-brand px-0">
      <span class="jv-brand-mark"><i class="bi bi-briefcase-fill"></i></span>
      JobVault
    </span>

    <div>
      <h1 class="jv-auth-headline">Vagas, empresas e talentos <em>com match inteligente</em>.</h1>
      <p class="jv-auth-lead">Empresas publicam vagas com tópicos e áreas de atuação; candidatos montam seu perfil técnico e descobrem na hora a compatibilidade com cada oportunidade.</p>
    </div>

    <ul class="jv-auth-points">
      <li><i class="bi bi-check2-circle"></i>Match automático por área, senioridade, modalidade e tópicos</li>
      <li><i class="bi bi-check2-circle"></i>Contas de Empresa com múltiplos recrutadores e vagas próprias</li>
      <li><i class="bi bi-check2-circle"></i>Perfil de candidato com bio, stack de tecnologias e links</li>
    </ul>

    <p class="jv-auth-foot text-muted small mb-0">© <?= date('Y') ?> JobVault</p>
  </section>

  <section class="jv-auth-main">
    <div class="jv-auth-card">
      <h2><?= $isSignup ? 'Crie sua conta' : 'Entre na sua conta' ?></h2>
      <p class="text-muted mb-0">Use seu e-mail e senha para continuar.</p>

      <div class="nav jv-segmented" role="tablist">
        <button class="nav-link <?= $isSignup ? '' : 'active' ?>" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-form" type="button" role="tab" aria-controls="login-form" aria-selected="<?= $isSignup ? 'false' : 'true' ?>">
          Entrar
        </button>
        <button class="nav-link <?= $isSignup ? 'active' : '' ?>" id="signup-tab" data-bs-toggle="pill" data-bs-target="#signup-form" type="button" role="tab" aria-controls="signup-form" aria-selected="<?= $isSignup ? 'true' : 'false' ?>">
          Criar conta
        </button>
      </div>

      <div class="tab-content">
        <div class="tab-pane fade <?= $isSignup ? '' : 'show active' ?>" id="login-form" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
          <?php if (!empty($alertaLogin)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
              <span><?= htmlspecialchars($alertaLogin) ?></span>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
          <?php endif; ?>

          <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="acao" value="logar">
            <?= Csrf::input() ?>

            <div class="mb-3">
              <label for="email-login" class="form-label">E-mail</label>
              <input type="email" class="form-control" id="email-login" name="email" value="<?= htmlspecialchars($loginEmail ?? '') ?>" placeholder="voce@empresa.com" autocomplete="email" required>
              <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>

            <div class="mb-3">
              <label for="password-login" class="form-label">Senha</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password-login" name="password" autocomplete="current-password" required>
                <button class="btn" type="button" data-toggle-password="#password-login" aria-label="Mostrar senha">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="invalid-feedback">Informe sua senha.</div>
            </div>

            <div class="mb-4 form-check">
              <input type="checkbox" class="form-check-input" id="remember-me" name="remember">
              <label class="form-check-label text-muted small" for="remember-me">Manter sessão neste dispositivo</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Entrar</button>
          </form>
        </div>

        <div class="tab-pane fade <?= $isSignup ? 'show active' : '' ?>" id="signup-form" role="tabpanel" aria-labelledby="signup-tab" tabindex="0">
          <?php if (!empty($alertaCadastro)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
              <span><?= htmlspecialchars($alertaCadastro) ?></span>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
          <?php endif; ?>

          <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="acao" value="cadastrar">
            <?= Csrf::input() ?>

            <fieldset class="mb-3">
              <legend class="form-label">Tipo de conta</legend>
              <div class="jv-choice">
                <div>
                  <input type="radio" class="btn-check" name="account_type" id="type-candidate" value="candidate" data-account-type="candidate" <?= !$isCompany ? 'checked' : '' ?>>
                  <label class="jv-choice-option" for="type-candidate">
                    <i class="bi bi-person-badge text-info mt-1" aria-hidden="true"></i>
                    <span><strong>Candidato</strong><small>Busco vagas e quero match</small></span>
                  </label>
                </div>
                <div>
                  <input type="radio" class="btn-check" name="account_type" id="type-company" value="company" data-account-type="company" <?= $isCompany ? 'checked' : '' ?>>
                  <label class="jv-choice-option" for="type-company">
                    <i class="bi bi-building text-info mt-1" aria-hidden="true"></i>
                    <span><strong>Empresa</strong><small>Publico vagas e avalio perfis</small></span>
                  </label>
                </div>
              </div>
            </fieldset>

            <div id="company-signup-fields" class="jv-company-fields <?= $isCompany ? '' : 'd-none' ?>">
              <div class="mb-3">
                <label for="company-name-signup" class="form-label">Nome da empresa</label>
                <input type="text" class="form-control" id="company-name-signup" name="company_name" value="<?= htmlspecialchars($companyName ?? '') ?>" placeholder="Ex.: TechNova Sistemas" <?= $isCompany ? 'required' : '' ?>>
                <div class="invalid-feedback">Informe o nome da sua empresa.</div>
              </div>
              <div class="mb-3">
                <label for="company-sector-signup" class="form-label">Setor de atuação</label>
                <input type="text" class="form-control" id="company-sector-signup" name="company_sector" value="<?= htmlspecialchars($companySector ?? 'Tecnologia') ?>" placeholder="Ex.: SaaS, Fintech, Consultoria">
              </div>
            </div>

            <div class="mb-3">
              <label for="name-signup" class="form-label">Seu nome completo</label>
              <input type="text" class="form-control" id="name-signup" name="name" value="<?= htmlspecialchars($signupName ?? '') ?>" autocomplete="name" required>
              <div class="invalid-feedback">Informe seu nome.</div>
            </div>

            <div class="mb-3">
              <label for="email-signup" class="form-label">E-mail</label>
              <input type="email" class="form-control" id="email-signup" name="email" value="<?= htmlspecialchars($signupEmail ?? '') ?>" placeholder="voce@empresa.com" autocomplete="email" required>
              <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>

            <div class="mb-3">
              <label for="password-signup" class="form-label">Senha</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password-signup" name="password" autocomplete="new-password" minlength="6" maxlength="72" aria-describedby="password-signup-help" required>
                <button class="btn" type="button" data-toggle-password="#password-signup" aria-label="Mostrar senha">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="password-signup-help" class="form-text">Mínimo de 6 caracteres.</div>
              <div class="invalid-feedback">A senha precisa ter pelo menos 6 caracteres.</div>
            </div>

            <div class="mb-4 form-check">
              <input type="checkbox" class="form-check-input" id="agree-terms" name="agree" required>
              <label class="form-check-label text-muted small" for="agree-terms">Concordo com os <a href="#">termos de uso</a></label>
              <div class="invalid-feedback">Aceite os termos para criar a conta.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Criar conta</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

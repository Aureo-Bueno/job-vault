<section class="container-content page-section py-3 py-lg-4">
  <div class="row justify-content-center align-items-stretch g-3 g-lg-4">
    <div class="col-12 col-lg-5">
      <div class="card h-100">
        <div class="card-body p-4 p-lg-5 d-flex flex-column justify-content-center">
          <span class="hero-chip mb-3 align-self-start">
            <i class="bi bi-shield-lock-fill"></i> Acesso seguro
          </span>
          <h2 class="section-title brand-font mb-3">JobVault</h2>
          <p class="section-subtitle mt-0 mb-3">
            Plataforma para organizar vagas, usuários e candidaturas em um único fluxo.
          </p>
          <ul class="list-unstyled mb-0 text-muted small d-grid gap-2">
            <li><i class="bi bi-check2-circle text-success me-1"></i> Controle de permissões por papel</li>
            <li><i class="bi bi-check2-circle text-success me-1"></i> Gestão rápida de vagas e status</li>
            <li><i class="bi bi-check2-circle text-success me-1"></i> Candidatura simplificada para usuários</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="card">
        <div class="card-body p-4 p-lg-5">
          <ul class="nav nav-pills nav-fill gap-2 mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link active fw-bold"
                id="login-tab"
                data-bs-toggle="pill"
                data-bs-target="#login-form"
                type="button"
                role="tab"
                aria-controls="login-form"
                aria-selected="true">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link fw-bold"
                id="signup-tab"
                data-bs-toggle="pill"
                data-bs-target="#signup-form"
                type="button"
                role="tab"
                aria-controls="signup-form"
                aria-selected="false">
                <i class="bi bi-person-plus-fill"></i> Cadastro
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <div class="tab-pane fade show active" id="login-form" role="tabpanel" aria-labelledby="login-tab">
              <?php if (!empty($alertaLogin)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-close="true">
                  <i class="bi bi-exclamation-circle-fill me-1"></i>
                  <?= htmlspecialchars($alertaLogin) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="acao" value="logar">
                <?= \App\Util\Csrf::input() ?>

                <div class="mb-3">
                  <label for="email-login" class="form-label"><i class="bi bi-envelope-fill"></i> E-mail</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email-login"
                    name="email"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required>
                  <div class="invalid-feedback" role="alert">Informe um e-mail válido.</div>
                </div>

                <div class="mb-3">
                  <label for="password-login" class="form-label"><i class="bi bi-lock-fill"></i> Senha</label>
                  <div class="input-group">
                    <input
                      type="password"
                      class="form-control"
                      id="password-login"
                      name="password"
                      placeholder="••••••••"
                      autocomplete="current-password"
                      required>
                    <button class="btn" type="button" id="toggle-password-login" aria-label="Mostrar senha">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  </div>
                  <div class="invalid-feedback" role="alert">Informe sua senha.</div>
                </div>

                <div class="mb-4 form-check">
                  <input type="checkbox" class="form-check-input" id="remember-me" name="remember">
                  <label class="form-check-label text-muted small" for="remember-me">
                    Manter sessão neste dispositivo
                  </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                  <i class="bi bi-box-arrow-in-right"></i> Entrar na plataforma
                </button>
              </form>
            </div>

            <div class="tab-pane fade" id="signup-form" role="tabpanel" aria-labelledby="signup-tab">
              <?php if (!empty($alertaCadastro)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-close="true">
                  <i class="bi bi-exclamation-circle-fill me-1"></i>
                  <?= htmlspecialchars($alertaCadastro) ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="acao" value="cadastrar">
                <?= \App\Util\Csrf::input() ?>

                <div class="mb-3">
                  <label for="name-signup" class="form-label"><i class="bi bi-person-fill"></i> Nome completo</label>
                  <input
                    type="text"
                    class="form-control"
                    id="name-signup"
                    name="name"
                    placeholder="Seu nome"
                    autocomplete="name"
                    required>
                  <div class="invalid-feedback" role="alert">Informe seu nome.</div>
                </div>

                <div class="mb-3">
                  <label for="email-signup" class="form-label"><i class="bi bi-envelope-fill"></i> E-mail</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email-signup"
                    name="email"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required>
                  <div class="invalid-feedback" role="alert">Informe um e-mail válido.</div>
                </div>

                <div class="mb-3">
                  <label for="password-signup" class="form-label"><i class="bi bi-lock-fill"></i> Senha</label>
                  <div class="input-group">
                    <input
                      type="password"
                      class="form-control"
                      id="password-signup"
                      name="password"
                      placeholder="••••••••"
                      autocomplete="new-password"
                      required>
                    <button class="btn" type="button" id="toggle-password-signup" aria-label="Mostrar senha">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  </div>
                  <small class="text-muted d-block mt-2">Use no mínimo 6 caracteres.</small>
                  <div class="invalid-feedback" role="alert">Informe uma senha válida.</div>
                </div>

                <div class="mb-4 form-check">
                  <input type="checkbox" class="form-check-input" id="agree-terms" name="agree" required>
                  <label class="form-check-label text-muted small" for="agree-terms">
                    Concordo com os <a href="#">termos de uso</a>
                  </label>
                  <div class="invalid-feedback" role="alert">Você deve concordar com os termos.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                  <i class="bi bi-person-plus-fill"></i> Criar conta
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

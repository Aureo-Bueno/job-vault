<!-- Container -->
<div class="container py-5">
  <div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-md-5 col-lg-4">
      <!-- Login Form Container -->
      <div class="card">
        <div class="card-body p-4">
          <!-- Header -->
          <div class="text-center mb-4">
            <div class="mb-3">
              <i class="bi bi-briefcase-fill fs-1 text-primary"></i>
            </div>
            <h2 class="text-white fw-bold">JobVault</h2>
            <p class="text-muted small">Sistema de Gerenciamento de Vagas</p>
          </div>

          <!-- Nav Tabs -->
          <ul class="nav nav-pills nav-fill gap-2 mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-bold" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-form" type="button" role="tab" aria-controls="login-form" aria-selected="true">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold" id="signup-tab" data-bs-toggle="pill" data-bs-target="#signup-form" type="button" role="tab" aria-controls="signup-form" aria-selected="false">
                <i class="bi bi-person-plus-fill"></i> Cadastro
              </button>
            </li>
          </ul>

          <!-- Tab Content -->
          <div class="tab-content">
            <!-- Login Form -->
            <div class="tab-pane fade show active" id="login-form" role="tabpanel" aria-labelledby="login-tab">
              <?php if (!empty($alertaLogin)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-circle-fill"></i>
                  <?= htmlspecialchars($alertaLogin) ?>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="acao" value="logar">

                <div class="mb-3">
                  <label for="email-login" class="form-label text-white">
                    <i class="bi bi-envelope-fill"></i> Email
                  </label>
                  <input type="email" class="form-control" id="email-login" name="email" placeholder="seu@email.com" required>
                  <div class="invalid-feedback text-danger">Por favor, informe um email válido.</div>
                </div>

                <div class="mb-3">
                  <label for="password-login" class="form-label text-white">
                    <i class="bi bi-lock-fill"></i> Senha
                  </label>
                  <div class="input-group">
                    <input type="password" class="form-control" id="password-login" name="password" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggle-password-login">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  </div>
                  <div class="invalid-feedback text-danger d-block">Por favor, informe sua senha.</div>
                </div>

                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="remember-me" name="remember">
                  <label class="form-check-label text-muted small" for="remember-me">
                    Lembrar-me neste dispositivo
                  </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                  <i class="bi bi-box-arrow-in-right"></i> Entrar
                </button>
              </form>
            </div>

            <!-- Signup Form -->
            <div class="tab-pane fade" id="signup-form" role="tabpanel" aria-labelledby="signup-tab">
              <?php if (!empty($alertaCadastro)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-circle-fill"></i>
                  <?= htmlspecialchars($alertaCadastro) ?>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

              <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="acao" value="cadastrar">

                <div class="mb-3">
                  <label for="name-signup" class="form-label text-white">
                    <i class="bi bi-person-fill"></i> Nome Completo
                  </label>
                  <input type="text" class="form-control" id="name-signup" name="name" placeholder="Seu Nome Completo" required>
                  <div class="invalid-feedback text-danger d-block">Por favor, informe seu nome.</div>
                </div>

                <div class="mb-3">
                  <label for="email-signup" class="form-label text-white">
                    <i class="bi bi-envelope-fill"></i> Email
                  </label>
                  <input type="email" class="form-control" id="email-signup" name="email" placeholder="seu@email.com" required>
                  <div class="invalid-feedback text-danger d-block">Por favor, informe um email válido.</div>
                </div>

                <div class="mb-3">
                  <label for="password-signup" class="form-label text-white">
                    <i class="bi bi-lock-fill"></i> Senha
                  </label>
                  <div class="input-group">
                    <input type="password" class="form-control" id="password-signup" name="password" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggle-password-signup">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  </div>
                  <div class="form-text text-muted small">
                    <i class="bi bi-info-circle-fill"></i> Mínimo 6 caracteres
                  </div>
                  <div class="invalid-feedback text-danger d-block">Por favor, informe uma senha.</div>
                </div>

                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="agree-terms" name="agree" required>
                  <label class="form-check-label text-muted small" for="agree-terms">
                    Concordo com os <a href="#" class="text-primary text-decoration-none">termos de uso</a>
                  </label>
                  <div class="invalid-feedback text-danger d-block">Você deve concordar com os termos.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                  <i class="bi bi-person-plus-fill"></i> Criar Conta
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mt-4">
        <p class="text-muted small">
          <i class="bi bi-shield-check"></i> Sua privacidade é importante para nós
        </p>
      </div>
    </div>
  </div>
</div>

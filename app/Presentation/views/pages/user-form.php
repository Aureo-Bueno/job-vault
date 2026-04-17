<?php
// Expecting: $user, $roles, $canAssignRole, $modo, $tituloPagina, $alerta
$tituloFormulario = $tituloPagina ?? ($modo === 'editar' ? 'Editar usuário' : 'Cadastrar usuário');
$senhaObrigatoria = $modo !== 'editar';
?>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-person-badge-fill text-info"></i> <?= htmlspecialchars($tituloFormulario) ?></h2>
      <p class="section-subtitle">Preencha os dados obrigatórios para salvar o usuário com segurança.</p>
    </div>
    <a href="index.php?r=users" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>

  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show mb-4" role="alert" data-auto-close="true">
      <i class="<?= htmlspecialchars($alerta['icone']) ?> me-1"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-4 p-lg-5">
      <form method="POST" class="needs-validation" novalidate>
        <?= \App\Util\Csrf::input() ?>

        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <label for="name" class="form-label">
              <i class="bi bi-person-fill"></i> Nome <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              class="form-control"
              id="name"
              name="name"
              value="<?= htmlspecialchars($user->name ?? '') ?>"
              required
              autocomplete="name">
            <div class="invalid-feedback" role="alert">Informe o nome do usuário.</div>
          </div>

          <div class="col-12 col-lg-6">
            <label for="email" class="form-label">
              <i class="bi bi-envelope-fill"></i> E-mail <span class="text-danger">*</span>
            </label>
            <input
              type="email"
              class="form-control"
              id="email"
              name="email"
              value="<?= htmlspecialchars($user->email ?? '') ?>"
              required
              autocomplete="email">
            <div class="invalid-feedback" role="alert">Informe um e-mail válido.</div>
          </div>

          <div class="col-12 col-lg-6">
            <label for="password" class="form-label">
              <i class="bi bi-lock-fill"></i> Senha <?= $senhaObrigatoria ? '<span class="text-danger">*</span>' : '' ?>
            </label>
            <input
              type="password"
              class="form-control"
              id="password"
              name="password"
              <?= $senhaObrigatoria ? 'required' : '' ?>
              placeholder="<?= $modo === 'editar' ? 'Deixe em branco para manter a senha atual' : 'Defina uma senha segura' ?>"
              autocomplete="new-password">
            <div class="invalid-feedback" role="alert">Informe uma senha válida.</div>
            <small class="text-muted d-block mt-2">Use no mínimo 6 caracteres.</small>
          </div>

          <?php if ($canAssignRole) : ?>
            <div class="col-12 col-lg-6">
              <label for="role_id" class="form-label">
                <i class="bi bi-shield-fill"></i> Papel de acesso
              </label>
              <select name="role_id" id="role_id" class="form-select">
                <option value="">Sem role</option>
                <?php foreach ($roles as $role) : ?>
                  <?php $roleIdValue = (string) ($role->id ?? ''); ?>
                  <option value="<?= htmlspecialchars($roleIdValue) ?>" <?= ($user->roleId ?? '') === $roleIdValue ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role->name) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-4 mt-2 border-top border-secondary-subtle">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg"></i> Salvar usuário
          </button>
          <a href="index.php?r=users" class="btn btn-outline-secondary px-4">
            <i class="bi bi-x-lg"></i> Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>
</section>

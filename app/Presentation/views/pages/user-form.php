<?php
// Expecting: $usuario, $roles, $podeAtribuirRole, $modo, $tituloPagina, $alerta
$tituloFormulario = $tituloPagina ?? ($modo === 'editar' ? 'Editar Usuário' : 'Cadastrar Usuário');
$senhaObrigatoria = $modo !== 'editar';
?>

<div class="container-content p-5">
  <div class="row mb-4">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-person-badge-fill text-primary"></i> <?= htmlspecialchars($tituloFormulario) ?>
      </h2>
      <p class="text-muted small mb-0">Preencha os dados para salvar o usuário</p>
    </div>
    <div class="col-auto">
      <a href="index.php?r=usuarios">
        <button type="button" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Voltar
        </button>
      </a>
    </div>
  </div>

  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show" role="alert">
      <i class="<?= htmlspecialchars($alerta['icone']) ?>"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-4">
      <form method="POST" class="needs-validation" novalidate>
        <div class="mb-4">
          <label for="nome" class="form-label text-white fw-bold">
            <i class="bi bi-person-fill"></i> Nome
          </label>
          <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario->nome ?? '') ?>" required>
          <div class="invalid-feedback text-danger">Informe o nome do usuário.</div>
        </div>

        <div class="mb-4">
          <label for="email" class="form-label text-white fw-bold">
            <i class="bi bi-envelope-fill"></i> Email
          </label>
          <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario->email ?? '') ?>" required>
          <div class="invalid-feedback text-danger">Informe um email válido.</div>
        </div>

        <div class="mb-4">
          <label for="senha" class="form-label text-white fw-bold">
            <i class="bi bi-lock-fill"></i> Senha
          </label>
          <input type="password" class="form-control" id="senha" name="senha" <?= $senhaObrigatoria ? 'required' : '' ?> placeholder="<?= $modo === 'editar' ? 'Deixe em branco para não alterar' : 'Defina uma senha' ?>">
          <div class="invalid-feedback text-danger">Informe uma senha válida.</div>
        </div>

        <?php if ($podeAtribuirRole) : ?>
          <div class="mb-4">
            <label for="role_id" class="form-label text-white fw-bold">
              <i class="bi bi-shield-fill"></i> Role
            </label>
            <select name="role_id" id="role_id" class="form-select">
              <option value="">Sem role</option>
              <?php foreach ($roles as $role) : ?>
                <option value="<?= (int) $role->id ?>" <?= (int) ($usuario->roleId ?? 0) === (int) $role->id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($role->nome) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="d-flex gap-3 pt-2">
          <button type="submit" class="btn btn-primary btn-lg fw-bold flex-grow-1">
            <i class="bi bi-check-lg"></i> Salvar
          </button>
          <a href="index.php?r=usuarios" class="btn btn-outline-secondary btn-lg fw-bold">
            <i class="bi bi-x-lg"></i> Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

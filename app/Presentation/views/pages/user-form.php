<?php
/**
 * @var object $user  {name, email, roleId, companyId?, id?}
 * @var \App\Application\DTOs\RoleDto[] $roles
 * @var \App\Application\DTOs\CompanyDto[] $companies
 * @var bool $canAssignRole
 * @var string $modo  'criar'|'editar'
 * @var string $tituloPagina
 * @var array|null $alerta
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$isEdit = $modo === 'editar';
$companies = $companies ?? [];
?>

<div class="jv-narrow">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow"><a href="index.php?r=users" class="text-reset text-decoration-none">Usuários</a> / <?= $isEdit ? 'Editar' : 'Novo' ?></p>
      <h1 class="jv-title"><?= htmlspecialchars($tituloPagina) ?></h1>
      <p class="jv-subtitle"><?= $isEdit ? 'Deixe a senha em branco para manter a atual.' : 'O usuário entra com o e-mail e a senha definidos aqui.' ?></p>
    </div>
  </header>

  <?php View::partial('alert', ['alerta' => $alerta ?? null]); ?>

  <form method="POST" class="jv-panel jv-panel-body needs-validation" novalidate>
    <?= Csrf::input() ?>

    <div class="jv-form-grid">
      <div>
        <label for="name" class="form-label">Nome</label>
        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user->name ?? '') ?>" autocomplete="name" required>
        <div class="invalid-feedback">Informe o nome do usuário.</div>
      </div>

      <div>
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>" autocomplete="email" required>
        <div class="invalid-feedback">Informe um e-mail válido.</div>
      </div>

      <div>
        <label for="password" class="form-label"><?= $isEdit ? 'Nova senha' : 'Senha' ?></label>
        <div class="input-group">
          <input type="password" class="form-control" id="password" name="password" minlength="6" maxlength="72" autocomplete="new-password" <?= $isEdit ? 'placeholder="Manter a atual"' : 'required' ?>>
          <button class="btn" type="button" data-toggle-password="#password" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-text">Mínimo de 6 caracteres.</div>
        <div class="invalid-feedback">A senha precisa ter pelo menos 6 caracteres.</div>
      </div>

      <?php if ($canAssignRole) : ?>
        <div>
          <label for="role_id" class="form-label">Papel de acesso</label>
          <select name="role_id" id="role_id" class="form-select">
            <option value=""><?= $isEdit ? 'Sem role' : 'Padrão (usuario)' ?></option>
            <?php foreach ($roles as $role) : ?>
              <option value="<?= htmlspecialchars($role->id) ?>" <?= ($user->roleId ?? '') === $role->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($role->name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div>
        <label for="company_id" class="form-label">Empresa vinculada (opcional)</label>
        <select name="company_id" id="company_id" class="form-select">
          <option value="">Nenhuma (Candidato / Admin)</option>
          <?php foreach ($companies as $company) : ?>
            <option value="<?= htmlspecialchars($company->id) ?>" <?= ($user->companyId ?? '') === $company->id ? 'selected' : '' ?>>
              <?= htmlspecialchars($company->name) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="jv-form-actions">
      <a href="index.php?r=users" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> <?= $isEdit ? 'Salvar alterações' : 'Criar usuário' ?>
      </button>
    </div>
  </form>
</div>

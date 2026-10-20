<?php
/**
 * @var \App\Application\DTOs\UserDto $user
 */
use App\Presentation\Support\Csrf;
?>

<div class="jv-confirm">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow"><a href="index.php?r=users" class="text-reset text-decoration-none">Usuários</a> / Excluir</p>
      <h1 class="jv-title">Excluir usuário</h1>
    </div>
  </header>

  <form method="POST" class="jv-panel jv-panel-body">
    <?= Csrf::input() ?>
    <div class="jv-confirm-icon" aria-hidden="true"><i class="bi bi-person-x"></i></div>
    <p class="mb-1">
      <strong class="text-white"><?= htmlspecialchars($user->name) ?></strong>
      <span class="text-muted">(<?= htmlspecialchars($user->email) ?>)</span> perderá o acesso e as candidaturas dele serão removidas.
    </p>
    <p class="text-muted mb-0">Essa ação não pode ser desfeita.</p>

    <div class="jv-form-actions">
      <a href="index.php?r=users" class="btn btn-ghost">Manter usuário</a>
      <button type="submit" name="excluir" value="1" class="btn btn-danger">
        <i class="bi bi-trash3"></i> Excluir usuário
      </button>
    </div>
  </form>
</div>

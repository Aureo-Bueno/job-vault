<?php
// Expecting: $user
?>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-trash-fill text-danger"></i> Excluir usuário</h2>
      <p class="section-subtitle">Essa ação remove o usuário e não pode ser desfeita.</p>
    </div>
  </div>

  <form method="POST" class="card">
    <?= \App\Util\Csrf::input() ?>
    <div class="card-body p-4">
      <p class="text-muted mb-4">
        Confirmar exclusão de <strong class="text-white"><?= htmlspecialchars($user->name ?? '') ?></strong>?
      </p>
      <div class="d-flex flex-wrap gap-2">
        <a href="index.php?r=users" class="btn btn-outline-secondary">
          Cancelar
        </a>
        <button type="submit" name="excluir" class="btn btn-danger">
          <i class="bi bi-trash-fill"></i> Excluir usuário
        </button>
      </div>
    </div>
  </form>
</section>

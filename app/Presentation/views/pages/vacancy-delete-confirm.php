<?php
// Expecting: $vacancy
?>

<section class="container-content page-section p-3 p-lg-4">
  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-trash-fill text-danger"></i> Excluir vaga</h2>
      <p class="section-subtitle">Essa ação remove a vaga definitivamente.</p>
    </div>
  </div>

  <form method="POST" class="card">
    <?= \App\Util\Csrf::input() ?>
    <div class="card-body p-4">
      <p class="text-muted mb-4">
        Confirmar exclusão da vaga <strong class="text-white"><?= htmlspecialchars($vacancy->title ?? '') ?></strong>?
      </p>

      <div class="d-flex flex-wrap gap-2">
        <a href="index.php?r=home" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" name="excluir" class="btn btn-danger">
          <i class="bi bi-trash-fill"></i> Excluir vaga
        </button>
      </div>
    </div>
  </form>
</section>

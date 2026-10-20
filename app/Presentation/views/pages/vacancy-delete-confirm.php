<?php
/**
 * @var \App\Application\DTOs\VacancyDto $vacancy
 */
use App\Presentation\Support\Csrf;
?>

<div class="jv-confirm">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow"><a href="index.php?r=home" class="text-reset text-decoration-none">Vagas</a> / Excluir</p>
      <h1 class="jv-title">Excluir vaga</h1>
    </div>
  </header>

  <form method="POST" class="jv-panel jv-panel-body">
    <?= Csrf::input() ?>
    <div class="jv-confirm-icon" aria-hidden="true"><i class="bi bi-trash3"></i></div>
    <p class="mb-1">
      A vaga <strong class="text-white"><?= htmlspecialchars($vacancy->title) ?></strong> será removida junto com as candidaturas recebidas.
    </p>
    <p class="text-muted mb-0">Essa ação não pode ser desfeita.</p>

    <div class="jv-form-actions">
      <a href="index.php?r=home" class="btn btn-ghost">Manter vaga</a>
      <button type="submit" name="excluir" value="1" class="btn btn-danger">
        <i class="bi bi-trash3"></i> Excluir vaga
      </button>
    </div>
  </form>
</div>

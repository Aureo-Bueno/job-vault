<?php
/**
 * @var array{tipo:string,icone:string,mensagem:string}|null $alerta
 */
if (empty($alerta)) {
  return;
}

$autoClose = in_array($alerta['tipo'], ['success', 'info'], true);
?>
<div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show mb-4" role="<?= $autoClose ? 'status' : 'alert' ?>"<?= $autoClose ? ' data-auto-close="true"' : '' ?>>
  <i class="<?= htmlspecialchars($alerta['icone']) ?>" aria-hidden="true"></i>
  <span><?= htmlspecialchars($alerta['mensagem']) ?></span>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>

<?php
/**
 * @var string $message
 */
?>
<div class="jv-state">
  <p class="jv-state-code">NÃO FOI POSSÍVEL CONCLUIR</p>
  <h1 class="jv-title mt-2">Algo impediu esta ação</h1>
  <p class="jv-subtitle mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
  <a href="index.php?r=home" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Voltar para o início</a>
</div>

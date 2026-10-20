<?php
/**
 * @var array<int,array{pagina:int,atual:bool}> $pagination  links from Pagination::links()
 * @var string $baseUrl      e.g. "index.php?r=vacancies"
 * @var string $queryString  preserved filters
 * @var string $summary      e.g. "15 vagas"
 */
if (empty($pagination)) {
  return;
}

$current = 1;
foreach ($pagination as $link) {
  if ($link['atual']) {
    $current = $link['pagina'];
  }
}
$last = count($pagination);
$url = static fn (int $page): string => $baseUrl . '&pagina=' . $page . ($queryString !== '' ? '&' . $queryString : '');
?>
<nav class="jv-pagination" aria-label="Paginação">
  <span><?= htmlspecialchars($summary) ?> · página <?= $current ?> de <?= $last ?></span>
  <div class="jv-pages">
    <a class="jv-page <?= $current <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars($url(max(1, $current - 1))) ?>" aria-label="Página anterior">
      <i class="bi bi-chevron-left"></i>
    </a>
    <?php foreach ($pagination as $link) : ?>
      <a class="jv-page <?= $link['atual'] ? 'active' : '' ?>" href="<?= htmlspecialchars($url($link['pagina'])) ?>"<?= $link['atual'] ? ' aria-current="page"' : '' ?>>
        <?= $link['pagina'] ?>
      </a>
    <?php endforeach; ?>
    <a class="jv-page <?= $current >= $last ? 'disabled' : '' ?>" href="<?= htmlspecialchars($url(min($last, $current + 1))) ?>" aria-label="Próxima página">
      <i class="bi bi-chevron-right"></i>
    </a>
  </div>
</nav>

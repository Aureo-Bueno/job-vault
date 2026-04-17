<?php
// Expecting: $alerta, $vacancies, $appliedMap
?>

<section class="container-content page-section p-3 p-lg-4">
  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show mb-4" role="alert" data-auto-close="true">
      <i class="<?= htmlspecialchars($alerta['icone']) ?> me-1"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <div class="section-head">
    <div>
      <h2 class="section-title"><i class="bi bi-send-check-fill text-success"></i> Candidaturas abertas</h2>
      <p class="section-subtitle">Escolha uma vaga ativa e envie sua candidatura em um clique.</p>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Título</th>
            <th>Descrição</th>
            <th>Publicada em</th>
            <th class="text-end">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vacancies)) : ?>
            <tr>
              <td colspan="4" class="empty-state">
                <i class="bi bi-inbox"></i> Nenhuma vaga ativa disponível no momento.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($vacancies as $vacancy) : ?>
              <?php
              $rawDescription = trim((string) ($vacancy->description ?? ''));
              $shortDescription = strlen($rawDescription) > 80 ? substr($rawDescription, 0, 80) . '...' : $rawDescription;
              $formattedDate = !empty($vacancy->createdAt) ? date('d/m/Y H:i', strtotime($vacancy->createdAt)) : '-';
              $vacancyId = (string) ($vacancy->id ?? '');
              $alreadyApplied = $vacancyId !== '' && isset($appliedMap[$vacancyId]);
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($vacancy->title ?? '') ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($shortDescription) ?></td>
                <td class="small text-nowrap"><?= htmlspecialchars($formattedDate) ?></td>
                <td>
                  <div class="d-flex justify-content-end">
                    <?php if ($alreadyApplied) : ?>
                      <span class="badge status-badge bg-success">
                        <i class="bi bi-check2-circle me-1"></i>Candidatura enviada
                      </span>
                    <?php else : ?>
                      <form method="POST" class="d-inline">
                        <?= \App\Util\Csrf::input() ?>
                        <input type="hidden" name="vacancy_id" value="<?= htmlspecialchars($vacancyId) ?>">
                        <button type="submit" class="btn btn-success btn-sm">
                          <i class="bi bi-send"></i> Candidatar
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

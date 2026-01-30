<?php
// Expecting: $alerta, $vacancies, $appliedMap
?>

<div class="container-content p-5">
  <?php if (!empty($alerta)) : ?>
    <div class="alert alert-<?= htmlspecialchars($alerta['tipo']) ?> alert-dismissible fade show" role="alert">
      <i class="<?= htmlspecialchars($alerta['icone']) ?>"></i>
      <?= htmlspecialchars($alerta['mensagem']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row mb-4 align-items-center">
    <div class="col">
      <h2 class="text-white fw-bold">
        <i class="bi bi-send-check-fill text-success"></i> Candidatar-se às Vagas
      </h2>
      <p class="text-muted small mb-0">Clique em candidatar para enviar sua inscrição</p>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="border-bottom">
          <tr>
            <th class="text-white fw-bold"><i class="bi bi-file-earmark-text"></i> Título</th>
            <th class="text-white fw-bold"><i class="bi bi-file-text"></i> Descrição</th>
            <th class="text-white fw-bold"><i class="bi bi-calendar-event"></i> Data</th>
            <th class="text-white fw-bold"><i class="bi bi-check-circle"></i> Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vacancies)) : ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-inbox"></i> Nenhuma vaga ativa disponível no momento.
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($vacancies as $vacancy) : ?>
              <?php
              $shortDescription = substr($vacancy->description ?? '', 0, 60);
              $formattedDate = !empty($vacancy->createdAt) ? date('d/m/Y H:i', strtotime($vacancy->createdAt)) : '-';
              $vacancyId = (string) ($vacancy->id ?? '');
              $alreadyApplied = $vacancyId !== '' && isset($appliedMap[$vacancyId]);
              ?>
              <tr class="align-middle">
                <td><strong><?= htmlspecialchars($vacancy->title ?? '') ?></strong></td>
                <td class="text-muted small"><?= htmlspecialchars($shortDescription) ?>...</td>
                <td class="small"><?= $formattedDate ?></td>
                <td>
                  <?php if ($alreadyApplied) : ?>
                    <span class="badge bg-success">Candidatura enviada</span>
                  <?php else : ?>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="vacancy_id" value="<?= htmlspecialchars($vacancyId) ?>">
                      <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-send"></i> Candidatar
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

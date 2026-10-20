<?php
/**
 * @var \App\Application\DTOs\VacancyDto $vacancy
 * @var list<\App\Application\DTOs\VacancyApplicantDto> $applicants
 */

$total = count($applicants);
$summary = $total === 1 ? '1 candidato inscrito' : $total . ' candidatos inscritos';
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow"><a href="index.php?r=home" class="text-reset text-decoration-none">Vagas</a> / Candidatos</p>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <h1 class="jv-title mb-0"><?= htmlspecialchars($vacancy->title) ?></h1>
      <?php if ($vacancy->companyName !== '') : ?>
        <span class="jv-badge jv-badge--info"><i class="bi bi-building me-1"></i><?= htmlspecialchars($vacancy->companyName) ?></span>
      <?php endif; ?>
      <span class="jv-badge"><?= htmlspecialchars($vacancy->areaLabel) ?> · <?= htmlspecialchars($vacancy->seniorityLabel) ?> · <?= htmlspecialchars($vacancy->workModeLabel) ?></span>
    </div>
    <p class="jv-subtitle"><?= htmlspecialchars($summary) ?> · ordenados pela maior compatibilidade (match) com os requisitos da vaga</p>
    <?php if (!empty($vacancy->topics)) : ?>
      <div class="jv-chips mt-2">
        <?php foreach ($vacancy->topics as $topic) : ?>
          <span class="jv-chip jv-chip--static"><?= htmlspecialchars($topic) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="jv-page-actions">
    <a href="index.php?r=home" class="btn btn-ghost">
      <i class="bi bi-arrow-left"></i> Voltar às vagas
    </a>
  </div>
</header>

<?php if ($total === 0) : ?>
  <section class="jv-panel jv-empty">
    <i class="bi bi-people" aria-hidden="true"></i>
    <strong>Nenhum candidato inscrito ainda</strong>
    Assim que os candidatos se inscreverem nesta vaga, você verá o perfil e o % de match de cada um aqui.
  </section>
<?php else : ?>
  <div class="jv-applicant-list">
    <?php foreach ($applicants as $applicant) : ?>
      <?php
      $appliedDate = $applicant->appliedAt !== '' ? date('d/m/Y H:i', strtotime($applicant->appliedAt)) : '—';
      $initial = mb_strtoupper(mb_substr($applicant->name, 0, 1));
      ?>
      <article class="jv-panel jv-applicant-card">
        <div class="jv-applicant-head">
          <div class="jv-user-cell">
            <span class="jv-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
            <div class="min-w-0">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="jv-applicant-name mb-0"><?= htmlspecialchars($applicant->name) ?></h2>
                <span class="jv-badge"><?= htmlspecialchars($applicant->areaLabel) ?></span>
                <span class="jv-badge"><?= htmlspecialchars($applicant->seniorityLabel) ?></span>
                <span class="jv-badge"><?= htmlspecialchars($applicant->workModeLabel) ?></span>
              </div>
              <p class="jv-cell-sub mb-0">
                <?= htmlspecialchars($applicant->email) ?>
                <?php if ($applicant->headline !== '') : ?>
                  · <strong class="text-light"><?= htmlspecialchars($applicant->headline) ?></strong>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="jv-match jv-match--<?= htmlspecialchars($applicant->matchLevel) ?>" title="Compatibilidade com a vaga">
              <i class="bi bi-stars" aria-hidden="true"></i>
              <?= (int) $applicant->matchScore ?>% match
            </span>
          </div>
        </div>

        <?php if ($applicant->bio !== '') : ?>
          <p class="jv-applicant-bio"><?= nl2br(htmlspecialchars($applicant->bio)) ?></p>
        <?php elseif (!$applicant->hasProfile) : ?>
          <p class="text-muted small mb-2">Este candidato ainda não preencheu o resumo e os tópicos do perfil.</p>
        <?php endif; ?>

        <?php if (!empty($applicant->matchedTopics) || !empty($applicant->missingTopics)) : ?>
          <div class="jv-applicant-topics">
            <?php foreach ($applicant->matchedTopics as $topic) : ?>
              <span class="jv-chip jv-chip--static jv-topic--match" title="Tópico em comum com a vaga">
                <i class="bi bi-check2"></i> <?= htmlspecialchars($topic) ?>
              </span>
            <?php endforeach; ?>
            <?php foreach ($applicant->missingTopics as $topic) : ?>
              <span class="jv-chip jv-chip--static jv-topic--missing" title="Tópico pedido na vaga, não listado pelo candidato">
                <?= htmlspecialchars($topic) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="jv-applicant-foot">
          <span class="jv-mono text-muted"><i class="bi bi-clock me-1"></i>Inscrito em <?= htmlspecialchars($appliedDate) ?></span>
          <div class="d-flex flex-wrap gap-2">
            <a href="mailto:<?= htmlspecialchars($applicant->email) ?>" class="btn btn-ghost btn-sm">
              <i class="bi bi-envelope"></i> Contatar
            </a>
            <?php if ($applicant->linkedinUrl !== '') : ?>
              <a href="<?= htmlspecialchars($applicant->linkedinUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">
                <i class="bi bi-linkedin"></i> LinkedIn
              </a>
            <?php endif; ?>
            <?php if ($applicant->githubUrl !== '') : ?>
              <a href="<?= htmlspecialchars($applicant->githubUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">
                <i class="bi bi-github"></i> GitHub / Portfólio
              </a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

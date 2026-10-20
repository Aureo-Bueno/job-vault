<?php
/**
 * @var array{tipo:string,icone:string,mensagem:string}|null $alerta
 * @var \App\Application\DTOs\VacancyDto $vacancy
 * @var \App\Application\DTOs\VacancyPipelineDto $pipeline
 */

use App\Presentation\Support\Csrf;
use App\Presentation\Support\Dates;
use App\Presentation\View;

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
$summary = $pipeline->total === 1 ? '1 candidato' : $pipeline->total . ' candidatos';
$closedStages = ['aprovada', 'reprovada', 'desistiu'];
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow"><a href="index.php?r=home" class="text-reset text-decoration-none">Vagas</a> / Pipeline</p>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <h1 class="jv-title mb-0"><?= $e($vacancy->title) ?></h1>
      <?php if ($vacancy->companyName !== '') : ?>
        <span class="jv-badge jv-badge--info"><i class="bi bi-building me-1"></i><?= $e($vacancy->companyName) ?></span>
      <?php endif; ?>
      <span class="jv-badge"><?= $e($vacancy->areaLabel) ?> · <?= $e($vacancy->seniorityLabel) ?> · <?= $e($vacancy->workModeLabel) ?></span>
      <span class="jv-status <?= $vacancy->isActive ? 'jv-status--active' : ($vacancy->isDraft() ? 'jv-status--draft' : 'jv-status--inactive') ?>">
        <?= $e($vacancy->statusLabel) ?><?= $vacancy->closeReasonLabel !== '' ? ' · ' . $e($vacancy->closeReasonLabel) : '' ?>
      </span>
    </div>
    <p class="jv-subtitle">
      <?= $e($summary) ?> · <?= (int) $pipeline->inProcess ?> em processo ·
      <strong class="text-light"><?= (int) $vacancy->filledPositions ?> de <?= (int) $vacancy->positions ?></strong> <?= $vacancy->positions === 1 ? 'posição preenchida' : 'posições preenchidas' ?>
      · cada coluna ordenada pelo maior match
    </p>
  </div>
  <div class="jv-page-actions">
    <a href="index.php?r=home" class="btn btn-ghost">
      <i class="bi bi-arrow-left"></i> Voltar às vagas
    </a>
  </div>
</header>

<?php View::partial('alert', ['alerta' => $alerta]); ?>

<?php if ($pipeline->total === 0) : ?>
  <section class="jv-panel jv-empty">
    <i class="bi bi-kanban" aria-hidden="true"></i>
    <strong>Nenhum candidato inscrito ainda</strong>
    Assim que os candidatos se inscreverem nesta vaga, eles aparecem na coluna “Recebida” com o % de match de cada um.
  </section>
<?php else : ?>
  <div class="jv-board" role="list" aria-label="Pipeline de candidaturas">
    <?php foreach ($pipeline->columns as $column) : ?>
      <section class="jv-board-col jv-stage--<?= $e($column->status) ?> <?= in_array($column->status, $closedStages, true) ? 'jv-board-col--closed' : '' ?>" role="listitem" aria-labelledby="col-<?= $e($column->status) ?>">
        <header class="jv-board-col-head">
          <span class="jv-stage-dot" aria-hidden="true"></span>
          <h2 id="col-<?= $e($column->status) ?>"><?= $e($column->label) ?></h2>
          <span class="jv-board-count"><?= $column->count() ?></span>
        </header>

        <div class="jv-board-cards">
          <?php if ($column->count() === 0) : ?>
            <p class="jv-board-empty">Ninguém nesta etapa</p>
          <?php endif; ?>

          <?php foreach ($column->applicants as $applicant) : ?>
            <?php $modalId = 'application-' . $applicant->applicationId; ?>
            <article class="jv-board-card">
              <div class="d-flex align-items-start justify-content-between gap-2">
                <div class="min-w-0">
                  <h3 class="jv-board-card-name"><?= $e($applicant->name) ?></h3>
                  <?php if ($applicant->headline !== '') : ?>
                    <p class="jv-board-card-sub"><?= $e($applicant->headline) ?></p>
                  <?php endif; ?>
                </div>
                <span class="jv-match jv-match--<?= $e($applicant->matchLevel) ?> jv-match--sm" title="Compatibilidade com a vaga">
                  <?= (int) $applicant->matchScore ?>%
                </span>
              </div>

              <div class="jv-board-card-meta">
                <span title="Na etapa desde <?= $e(Dates::dateTime($applicant->statusChangedAt)) ?>">
                  <i class="bi bi-clock" aria-hidden="true"></i> <?= $e(Dates::since($applicant->statusChangedAt)) ?>
                </span>
                <?php if ($applicant->notes !== []) : ?>
                  <span title="Notas internas"><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?= count($applicant->notes) ?></span>
                <?php endif; ?>
                <?php if (!$applicant->hasProfile) : ?>
                  <span class="text-warning" title="Perfil incompleto"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> perfil</span>
                <?php endif; ?>
              </div>

              <div class="jv-board-card-actions">
                <?php if ($applicant->canAdvance()) : ?>
                  <form method="POST" class="m-0 flex-grow-1">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="move">
                    <input type="hidden" name="move" value="advance">
                    <input type="hidden" name="application_id" value="<?= $e($applicant->applicationId) ?>">
                    <input type="hidden" name="expected_status" value="<?= $e($applicant->status) ?>">
                    <button type="submit" class="btn btn-primary btn-sm w-100" aria-label="Mover <?= $e($applicant->name) ?> para <?= $e((string) $applicant->nextStatusLabel) ?>">
                      <?= $e((string) $applicant->nextStatusLabel) ?> <i class="bi bi-arrow-right-short"></i>
                    </button>
                  </form>
                <?php elseif ($applicant->canReopen) : ?>
                  <form method="POST" class="m-0 flex-grow-1">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="move">
                    <input type="hidden" name="move" value="reopen">
                    <input type="hidden" name="application_id" value="<?= $e($applicant->applicationId) ?>">
                    <input type="hidden" name="expected_status" value="<?= $e($applicant->status) ?>">
                    <button type="submit" class="btn btn-ghost btn-sm w-100">
                      <i class="bi bi-arrow-counterclockwise"></i> Reabrir
                    </button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#<?= $e($modalId) ?>" title="Detalhes, histórico e notas" aria-label="Detalhes de <?= $e($applicant->name) ?>">
                  <i class="bi bi-layout-sidebar-reverse"></i><span class="visually-hidden">Detalhes</span>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>

  <?php foreach ($pipeline->columns as $column) : ?>
    <?php foreach ($column->applicants as $applicant) : ?>
      <?php $modalId = 'application-' . $applicant->applicationId; ?>
      <div class="modal fade" id="<?= $e($modalId) ?>" tabindex="-1" aria-labelledby="<?= $e($modalId) ?>-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
          <div class="modal-content jv-modal">
            <div class="modal-header">
              <div class="min-w-0">
                <h2 class="modal-title fs-5" id="<?= $e($modalId) ?>-title"><?= $e($applicant->name) ?></h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                  <span class="jv-stage-pill jv-stage--<?= $e($applicant->status) ?>"><span class="jv-stage-dot"></span><?= $e($applicant->statusLabel) ?></span>
                  <span class="jv-match jv-match--<?= $e($applicant->matchLevel) ?> jv-match--sm"><i class="bi bi-stars"></i> <?= (int) $applicant->matchScore ?>% match</span>
                  <span class="jv-badge"><?= $e($applicant->areaLabel) ?></span>
                  <span class="jv-badge"><?= $e($applicant->seniorityLabel) ?></span>
                  <span class="jv-badge"><?= $e($applicant->workModeLabel) ?></span>
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
              <div class="jv-detail-grid">
                <div>
                  <h3 class="jv-detail-title">Perfil</h3>
                  <?php if ($applicant->headline !== '') : ?>
                    <p class="text-light fw-semibold mb-1"><?= $e($applicant->headline) ?></p>
                  <?php endif; ?>
                  <?php if ($applicant->bio !== '') : ?>
                    <p class="jv-applicant-bio"><?= nl2br($e($applicant->bio)) ?></p>
                  <?php else : ?>
                    <p class="text-muted small">O candidato ainda não preencheu o resumo do perfil.</p>
                  <?php endif; ?>

                  <?php if ($applicant->matchedTopics !== [] || $applicant->missingTopics !== []) : ?>
                    <div class="jv-applicant-topics mb-3">
                      <?php foreach ($applicant->matchedTopics as $topic) : ?>
                        <span class="jv-chip jv-chip--static jv-topic--match" title="Tópico em comum com a vaga"><i class="bi bi-check2"></i> <?= $e($topic) ?></span>
                      <?php endforeach; ?>
                      <?php foreach ($applicant->missingTopics as $topic) : ?>
                        <span class="jv-chip jv-chip--static jv-topic--missing" title="Pedido na vaga, não listado pelo candidato"><?= $e($topic) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="mailto:<?= $e($applicant->email) ?>" class="btn btn-ghost btn-sm"><i class="bi bi-envelope"></i> <?= $e($applicant->email) ?></a>
                    <?php if ($applicant->linkedinUrl !== '') : ?>
                      <a href="<?= $e($applicant->linkedinUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm"><i class="bi bi-linkedin"></i> LinkedIn</a>
                    <?php endif; ?>
                    <?php if ($applicant->githubUrl !== '') : ?>
                      <a href="<?= $e($applicant->githubUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm"><i class="bi bi-github"></i> GitHub</a>
                    <?php endif; ?>
                  </div>

                  <h3 class="jv-detail-title">Notas internas <span class="text-muted fw-normal">· não aparecem para o candidato</span></h3>
                  <?php if ($applicant->notes === []) : ?>
                    <p class="text-muted small">Nenhuma nota ainda.</p>
                  <?php else : ?>
                    <ul class="jv-notes">
                      <?php foreach ($applicant->notes as $note) : ?>
                        <li>
                          <p class="mb-1"><?= nl2br($e($note->body)) ?></p>
                          <span class="jv-mono text-muted small"><?= $e($note->authorName) ?> · <?= $e(Dates::dateTime($note->createdAt)) ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                  <form method="POST" class="mt-2">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="action" value="note">
                    <input type="hidden" name="application_id" value="<?= $e($applicant->applicationId) ?>">
                    <label class="visually-hidden" for="<?= $e($modalId) ?>-note">Nova nota</label>
                    <textarea id="<?= $e($modalId) ?>-note" name="body" class="form-control mb-2" rows="2" maxlength="2000" required placeholder="Ex.: entrevista marcada para quinta, 15h."></textarea>
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="bi bi-plus-lg"></i> Adicionar nota</button>
                  </form>
                </div>

                <div>
                  <h3 class="jv-detail-title">Histórico</h3>
                  <ol class="jv-timeline">
                    <?php foreach ($applicant->history as $event) : ?>
                      <li class="jv-stage--<?= $e($event->status) ?>">
                        <span class="jv-stage-dot" aria-hidden="true"></span>
                        <div>
                          <strong><?= $e($event->label) ?></strong>
                          <span class="d-block jv-mono text-muted small"><?= $e(Dates::dateTime($event->at)) ?> · <?= $e($event->actorName) ?></span>
                          <?php if ($event->reason !== '') : ?>
                            <p class="jv-timeline-reason">“<?= $e($event->reason) ?>”</p>
                          <?php endif; ?>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ol>

                  <?php if ($applicant->canReject) : ?>
                    <form method="POST" class="jv-reject-box" data-confirm="Reprovar <?= $e($applicant->name) ?>? O candidato verá o status “Não selecionada”.">
                      <?= Csrf::input() ?>
                      <input type="hidden" name="action" value="move">
                      <input type="hidden" name="move" value="reject">
                      <input type="hidden" name="application_id" value="<?= $e($applicant->applicationId) ?>">
                      <input type="hidden" name="expected_status" value="<?= $e($applicant->status) ?>">
                      <label class="form-label small" for="<?= $e($modalId) ?>-feedback">Retorno ao candidato <span class="text-muted">(opcional, visível para ele)</span></label>
                      <textarea id="<?= $e($modalId) ?>-feedback" name="feedback" class="form-control mb-2" rows="2" maxlength="500" placeholder="Ex.: seguimos com um perfil com mais experiência em Go."></textarea>
                      <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i> Reprovar</button>
                    </form>
                  <?php elseif ($applicant->candidateFeedback !== '') : ?>
                    <div class="jv-feedback-box">
                      <span class="small text-muted d-block mb-1">Retorno enviado ao candidato</span>
                      <?= nl2br($e($applicant->candidateFeedback)) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
<?php endif; ?>

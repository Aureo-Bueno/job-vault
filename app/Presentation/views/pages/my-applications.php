<?php
/**
 * @var array{tipo:string,icone:string,mensagem:string}|null $alerta
 * @var list<\App\Application\DTOs\MyApplicationDto> $applications
 */

use App\Presentation\Support\Csrf;
use App\Presentation\Support\Dates;
use App\Presentation\View;

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
$steps = ['recebida' => 'Enviada', 'em_analise' => 'Em análise', 'entrevista' => 'Entrevista', 'aprovada' => 'Aprovada'];
$total = count($applications);
$inProcess = count(array_filter($applications, static fn ($a): bool => $a->canWithdraw));
?>

<header class="jv-page-head">
  <div>
    <p class="jv-eyebrow">Candidaturas &amp; Match</p>
    <h1 class="jv-title">Minhas candidaturas</h1>
    <p class="jv-subtitle">
      <?= $total === 1 ? '1 candidatura' : $total . ' candidaturas' ?>
      <?php if ($inProcess > 0) : ?>· <?= $inProcess ?> em andamento<?php endif; ?>
    </p>
  </div>
  <div class="jv-page-actions">
    <a href="index.php?r=vacancies/apply" class="btn btn-ghost"><i class="bi bi-search"></i> Ver vagas abertas</a>
  </div>
</header>

<?php View::partial('alert', ['alerta' => $alerta]); ?>

<?php if ($total === 0) : ?>
  <section class="jv-panel jv-empty">
    <i class="bi bi-send" aria-hidden="true"></i>
    <strong>Você ainda não se candidatou a nenhuma vaga</strong>
    Encontre vagas com alto match para o seu perfil e acompanhe cada etapa por aqui.
  </section>
<?php else : ?>
  <div class="jv-my-applications">
    <?php foreach ($applications as $application) : ?>
      <?php
      $reached = array_fill_keys(array_map(static fn ($event): string => $event->status, $application->timeline), true);
      $isClosedOut = in_array($application->status, ['reprovada', 'desistiu'], true);
      ?>
      <article class="jv-panel jv-my-application jv-stage--<?= $e($application->status) ?>">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
          <div class="min-w-0">
            <?php if ($application->companyName !== '') : ?>
              <span class="jv-job-company"><i class="bi bi-building me-1"></i><?= $e($application->companyName) ?></span>
            <?php endif; ?>
            <h2 class="jv-my-application-title"><?= $e($application->vacancyTitle) ?></h2>
          </div>
          <span class="jv-stage-pill jv-stage--<?= $e($application->status) ?>"><span class="jv-stage-dot"></span><?= $e($application->statusLabel) ?></span>
        </div>

        <ol class="jv-stepper" aria-label="Etapas da candidatura">
          <?php foreach ($steps as $step => $label) : ?>
            <?php
            $state = match (true) {
              $application->status === $step => 'current',
              isset($reached[$step]) => 'done',
              default => 'todo',
            };
            ?>
            <li class="jv-step jv-step--<?= $state ?>" <?= $state === 'current' ? 'aria-current="step"' : '' ?>>
              <span class="jv-step-mark" aria-hidden="true">
                <?php if ($state === 'done') : ?><i class="bi bi-check2"></i><?php endif; ?>
              </span>
              <span class="jv-step-label"><?= $e($label) ?></span>
            </li>
          <?php endforeach; ?>
          <?php if ($isClosedOut) : ?>
            <li class="jv-step jv-step--current jv-step--<?= $e($application->status) ?>" aria-current="step">
              <span class="jv-step-mark" aria-hidden="true"><i class="bi <?= $application->status === 'reprovada' ? 'bi-x' : 'bi-dash' ?>"></i></span>
              <span class="jv-step-label"><?= $e($application->statusLabel) ?></span>
            </li>
          <?php endif; ?>
        </ol>

        <?php if ($application->feedback !== '') : ?>
          <div class="jv-feedback-box">
            <span class="small text-muted d-block mb-1"><i class="bi bi-chat-quote me-1"></i>Retorno da empresa</span>
            <?= nl2br($e($application->feedback)) ?>
          </div>
        <?php endif; ?>

        <details class="jv-history">
          <summary>Histórico</summary>
          <ol class="jv-timeline">
            <?php foreach ($application->timeline as $event) : ?>
              <li class="jv-stage--<?= $e($event->status) ?>">
                <span class="jv-stage-dot" aria-hidden="true"></span>
                <div>
                  <strong><?= $e($event->label) ?></strong>
                  <span class="d-block jv-mono text-muted small"><?= $e(Dates::dateTime($event->at)) ?></span>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        </details>

        <div class="jv-applicant-foot">
          <span class="jv-mono text-muted">
            <i class="bi bi-clock me-1"></i>Enviada em <?= $e(Dates::date($application->appliedAt)) ?>
            · atualizada <?= $e(Dates::since($application->statusChangedAt)) ?>
          </span>
          <div class="d-flex flex-wrap gap-2">
            <?php if ($application->canWithdraw) : ?>
              <form method="POST" class="m-0" data-confirm="Desistir da candidatura para “<?= $e($application->vacancyTitle) ?>”?">
                <?= Csrf::input() ?>
                <input type="hidden" name="action" value="withdraw">
                <input type="hidden" name="application_id" value="<?= $e($application->applicationId) ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-left"></i> Desistir</button>
              </form>
            <?php elseif ($application->canReapply) : ?>
              <form method="POST" class="m-0">
                <?= Csrf::input() ?>
                <input type="hidden" name="action" value="reapply">
                <input type="hidden" name="vacancy_id" value="<?= $e($application->vacancyId) ?>">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-send"></i> Candidatar novamente</button>
              </form>
            <?php elseif ($application->status === 'desistiu' && !$application->vacancyOpen) : ?>
              <span class="text-muted small">Vaga encerrada</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

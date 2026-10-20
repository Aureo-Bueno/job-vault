<?php
/**
 * @var \App\Application\DTOs\CandidateProfileDto $profile
 * @var array|null $alerta
 * @var array<string,string> $areas
 * @var array<string,string> $seniorities
 * @var array<string,string> $workModes
 * @var list<string> $popularTopics
 */
use App\Presentation\Support\Csrf;
use App\Presentation\View;

$topicsCsv = implode(', ', $profile->topics);
$topicMapLower = array_fill_keys(array_map('mb_strtolower', $profile->topics), true);
?>

<div class="jv-narrow">
  <header class="jv-page-head">
    <div>
      <p class="jv-eyebrow">Candidato</p>
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="jv-title mb-0">Meu perfil profissional</h1>
        <?php if ($profile->isComplete) : ?>
          <span class="jv-badge jv-badge--success"><i class="bi bi-stars"></i> Match ativo</span>
        <?php else : ?>
          <span class="jv-badge jv-badge--info"><i class="bi bi-info-circle"></i> Perfil incompleto</span>
        <?php endif; ?>
      </div>
      <p class="jv-subtitle">Descreva sua trajetória, escolha sua área e adicione seus tópicos técnicos para encontrarmos as vagas mais compatíveis com você.</p>
    </div>
    <div class="jv-page-actions">
      <a href="index.php?r=vacancies/apply" class="btn btn-ghost">
        <i class="bi bi-send"></i> Ver vagas abertas
      </a>
    </div>
  </header>

  <?php View::partial('alert', ['alerta' => $alerta]); ?>

  <form method="POST" class="jv-panel jv-panel-body needs-validation" novalidate>
    <?= Csrf::input() ?>

    <div class="mb-4">
      <label for="headline" class="form-label">Título profissional</label>
      <input
        type="text"
        class="form-control form-control-lg"
        id="headline"
        name="headline"
        maxlength="160"
        placeholder="Ex.: Desenvolvedor(a) Backend PHP & MySQL"
        value="<?= htmlspecialchars($profile->headline) ?>"
        required>
      <div class="form-text">Resumo em uma linha que aparece no topo do seu cartão para as empresas.</div>
      <div class="invalid-feedback">Informe um título curto para o seu perfil.</div>
    </div>

    <div class="mb-4">
      <label for="bio" class="form-label">Sobre você e sua experiência</label>
      <textarea
        class="form-control"
        id="bio"
        name="bio"
        rows="5"
        maxlength="3000"
        placeholder="Conte um pouco sobre os projetos em que trabalhou, tecnologias que domina e que tipo de desafio procura..."><?= htmlspecialchars($profile->bio) ?></textarea>
    </div>

    <div class="jv-form-grid mb-4">
      <div>
        <label for="area" class="form-label">Área de atuação principal</label>
        <select id="area" name="area" class="form-select">
          <?php foreach ($areas as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $profile->area === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="seniority" class="form-label">Nível de senioridade</label>
        <select id="seniority" name="seniority" class="form-select">
          <?php foreach ($seniorities as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $profile->seniority === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="work_mode" class="form-label">Modalidade preferida</label>
        <select id="work_mode" name="work_mode" class="form-select">
          <?php foreach ($workModes as $value => $label) : ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $profile->workMode === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-4" data-topic-picker>
      <label for="topics-input" class="form-label">Tópicos, linguagens e ferramentas que você domina</label>
      <input type="hidden" name="topics" value="<?= htmlspecialchars($topicsCsv) ?>" data-topic-hidden>

      <div class="jv-topic-box">
        <div class="jv-chips" data-topic-list>
          <?php foreach ($profile->topics as $topic) : ?>
            <span class="jv-chip" data-topic-chip="<?= htmlspecialchars($topic) ?>">
              <?= htmlspecialchars($topic) ?>
              <button type="button" data-topic-remove="<?= htmlspecialchars($topic) ?>" aria-label="Remover <?= htmlspecialchars($topic) ?>">
                <i class="bi bi-x-lg"></i>
              </button>
            </span>
          <?php endforeach; ?>
        </div>
        <div class="jv-topic-entry">
          <input
            type="text"
            id="topics-input"
            class="form-control form-control-sm"
            placeholder="Digite um tópico (ex.: PHP, React, Docker) e pressione Enter ou vírgula"
            data-topic-input
            autocomplete="off">
          <button type="button" class="btn btn-ghost btn-sm" data-topic-add-btn>
            <i class="bi bi-plus-lg"></i> Adicionar
          </button>
        </div>
      </div>
      <div class="form-text mb-2">Esses tópicos são comparados diretamente com os requisitos das vagas para calcular seu % de match.</div>

      <div class="jv-topic-suggestions">
        <span class="text-muted small me-1">Sugestões rápidas:</span>
        <?php foreach ($popularTopics as $suggestion) : ?>
          <?php $isAdded = isset($topicMapLower[mb_strtolower($suggestion)]); ?>
          <button
            type="button"
            class="jv-suggestion-chip <?= $isAdded ? 'is-added' : '' ?>"
            data-topic-suggest="<?= htmlspecialchars($suggestion) ?>">
            <i class="bi <?= $isAdded ? 'bi-check2' : 'bi-plus' ?>"></i> <?= htmlspecialchars($suggestion) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="jv-form-grid">
      <div>
        <label for="linkedin_url" class="form-label"><i class="bi bi-linkedin me-1"></i> LinkedIn (opcional)</label>
        <input
          type="url"
          class="form-control"
          id="linkedin_url"
          name="linkedin_url"
          placeholder="https://linkedin.com/in/seu-perfil"
          value="<?= htmlspecialchars($profile->linkedinUrl) ?>">
      </div>

      <div>
        <label for="github_url" class="form-label"><i class="bi bi-github me-1"></i> GitHub ou Portfólio (opcional)</label>
        <input
          type="url"
          class="form-control"
          id="github_url"
          name="github_url"
          placeholder="https://github.com/seu-usuario"
          value="<?= htmlspecialchars($profile->githubUrl) ?>">
      </div>
    </div>

    <div class="jv-form-actions">
      <a href="index.php?r=vacancies/apply" class="btn btn-ghost">Ir para vagas</a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check2"></i> Salvar perfil
      </button>
    </div>
  </form>
</div>

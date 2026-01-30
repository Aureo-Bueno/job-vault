<?php
// Expecting: $user
?>

<main class="container-content p-5">
  <h2 class="mt-3 text-white fw-bold">Excluir Usuário</h2>

  <form method="POST" class="mt-4">
    <div class="card">
      <div class="card-body">
        <p class="text-muted">
          Você deseja realmente excluir o usuário <strong><?= htmlspecialchars($user->name ?? '') ?></strong>?
        </p>
        <div class="d-flex gap-3">
          <a href="index.php?r=users" class="btn btn-outline-secondary">
            Cancelar
          </a>
          <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
        </div>
      </div>
    </div>
  </form>
</main>

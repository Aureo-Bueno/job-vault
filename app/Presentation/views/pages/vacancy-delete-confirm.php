<main class="container-content p-5">
  <h2 class="mt-3">Excluir Vaga</h2>

  <form method="POST">
    <div class="form-group">
      <p>Você deseja realmente excluir a Vaga <strong><?= htmlspecialchars($obVaga->titulo ?? '') ?></strong>?</p>
    </div>



    <div class="form-group mb-5">
      <a href="index.php?r=home">
        <button type="button" class="btn btn-outline-secondary">Cancelar</button>
      </a>
      <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
    </div>

  </form>
</main>
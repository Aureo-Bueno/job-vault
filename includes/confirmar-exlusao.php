<main>
  <h2 class="mt-3">Excluir Vaga</h2>

  <form method="POST">
    <div class="form-group">
      <p>Você deseja realmente exluir a Vaga <strong><?= $obVaga->titulo  ?></strong>?</p>
    </div>



    <div class="form-goup mb-5">
      <a href="index.php">
        <button type="button" class="btn btn-success">Cancelar</button>
      </a>
      <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
    </div>

  </form>
</main>
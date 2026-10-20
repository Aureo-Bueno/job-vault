<?php
// Closes the structure opened in header.php (shell for logged users, bare <main> for guests).
$showShell = ($loggedUser ?? null) !== null;
$appJs = 'assets/js/app.js';
$appJsFile = BASE_PATH . '/public/' . $appJs;
$appJs .= is_file($appJsFile) ? '?v=' . filemtime($appJsFile) : '';
?>
<?php if ($showShell) : ?>
      </main>

      <footer class="jv-footer">
        © <?= date('Y') ?> JobVault. Todos os direitos reservados.
      </footer>
    </div>
  </div>
<?php else : ?>
  </main>
<?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= htmlspecialchars($appJs) ?>"></script>
</body>

</html>

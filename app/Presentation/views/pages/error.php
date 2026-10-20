<?php
// Expecting: $message
?>

<section class="container-content page-section p-3 p-lg-4">
  <div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-octagon-fill me-1"></i>
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
  </div>
</section>

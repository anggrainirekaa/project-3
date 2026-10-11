<?php
// Variabel: $page, $pages, $query (array parameter GET tanpa 'page')
if ($pages > 1): ?>
<div class="pagination">
  <?php for ($i = 1; $i <= $pages; $i++):
      $qs = http_build_query(array_merge($query, ['page' => $i])); ?>
    <a class="<?= $i === $page ? 'current' : '' ?>" href="?<?= e($qs) ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
